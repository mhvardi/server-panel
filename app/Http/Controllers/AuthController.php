<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function __construct(protected GeoIpService $geoIp)
    {
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $ip = $request->ip();
        $email = $request->input('email');
        $throttleKey = 'login_throttle:' . sha1($ip . '|' . strtolower($email));
        $country = $this->geoIp->getCountryCode($ip);

        if (Auth::attempt($credentials)) {
            // Clear rate limiting counter on success
            RateLimiter::clear($throttleKey);

            // Log successful attempt
            if (SecuritySetting::isTrue('log_login_attempts', true)) {
                LoginAttempt::create([
                    'email' => $email,
                    'ip_address' => $ip,
                    'country' => $country,
                    'success' => true,
                    'user_agent' => $request->userAgent(),
                ]);

                SecurityEvent::log(
                    'login',
                    'info',
                    "ورود موفقیت‌آمیز به پنل ({$email})",
                    "ورود از طریق آی‌پی {$ip} ({$country}) انجام شد.",
                    ['ip' => $ip, 'country' => $country],
                    $ip
                );
            }

            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        // Increment rate limiter on failure (3 attempts -> 24h)
        $lockoutMinutes = (int) SecuritySetting::get('lockout_minutes', 1440);
        RateLimiter::hit($throttleKey, $lockoutMinutes * 60);

        // Record failed attempt
        if (SecuritySetting::isTrue('log_login_attempts', true)) {
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'country' => $country,
                'success' => false,
                'user_agent' => $request->userAgent(),
                'blocked_reason' => 'invalid_credentials',
            ]);

            SecurityEvent::log(
                'login',
                'warning',
                "تلاش ناموفق برای ورود به پنل ({$email})",
                "رمز عبور اشتباه وارد شد. آی‌پی: {$ip} ({$country})",
                ['ip' => $ip, 'attempts' => RateLimiter::attempts($throttleKey)],
                $ip
            );
        }

        $remaining = max(0, (int) SecuritySetting::get('max_login_attempts', 3) - RateLimiter::attempts($throttleKey));
        $warning = $remaining > 0
            ? "اطلاعات ورود نامعتبر است. ({$remaining} تلاش دیگر تا مسدودی ۲۴ ساعته باقیست)"
            : "تعداد تلاش‌ها به حد مجاز رسید و دسترسی برای ۲۴ ساعت مسدود گردید.";

        return back()->withErrors([
            'email' => $warning,
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
