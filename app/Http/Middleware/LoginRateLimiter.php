<?php

namespace App\Http\Middleware;

use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only rate limit on POST login submissions
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        $ip = $request->ip();
        $email = (string) $request->input('email', '');
        $throttleKey = 'login_throttle:' . sha1($ip . '|' . strtolower($email));

        $maxAttempts = (int) SecuritySetting::get('max_login_attempts', 3);
        $lockoutMinutes = (int) SecuritySetting::get('lockout_minutes', 1440); // default 24h (1440 minutes)
        $lockoutSeconds = $lockoutMinutes * 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $hours = ceil($seconds / 3600);

            // Record blocked login attempt
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'success' => false,
                'user_agent' => $request->userAgent(),
                'blocked_reason' => 'rate_limit_exceeded',
            ]);

            SecurityEvent::log(
                'login',
                'critical',
                "آی‌پی {$ip} به دلیل بیش از حد بودن تلاش‌های ناموفق به مدت {$hours} ساعت مسدود شد.",
                "ایمیل هدف: {$email}",
                ['ip' => $ip, 'available_in_seconds' => $seconds],
                $ip
            );

            return back()->withErrors([
                'email' => "تعداد تلاش‌های ناموفق بیش از ۳ بار بوده است. به دلایل امنیتی ورود برای شما به مدت ۲۴ ساعت مسدود گردید. (زمان باقیمانده: {$hours} ساعت)",
            ])->onlyInput('email');
        }

        return $next($request);
    }
}
