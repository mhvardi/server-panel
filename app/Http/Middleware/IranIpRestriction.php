<?php

namespace App\Http\Middleware;

use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Services\GeoIpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IranIpRestriction
{
    public function __construct(protected GeoIpService $geoIp)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if Iran IP restriction is active
        if (!SecuritySetting::isTrue('iran_ip_restriction', false)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // 2. Allow if Iran IP or Whitelisted
        if ($this->geoIp->isIranIp($clientIp)) {
            return $next($request);
        }

        // 3. Log blocked attempt
        $country = $this->geoIp->getCountryCode($clientIp);
        LoginAttempt::create([
            'email' => $request->input('email'),
            'ip_address' => $clientIp,
            'country' => $country,
            'success' => false,
            'user_agent' => $request->userAgent(),
            'blocked_reason' => 'geo_restriction_non_iran',
        ]);

        SecurityEvent::log(
            'login',
            'warning',
            "مسدودسازی ورود به علت موقعیت جغرافیایی غیرمجاز ({$country})",
            "آی‌پی: {$clientIp} | کشور: {$country} | آدرس درخواست: {$request->path()}",
            ['ip' => $clientIp, 'country' => $country],
            $clientIp
        );

        // Render clean Persian forbidden response
        return response()->view('errors.403_security', [
            'ip' => $clientIp,
            'country' => $country,
            'message' => 'دسترسی به این بخش تنها از طریق شبکه و آی‌پی‌های ایران امکان‌پذیر می‌باشد.'
        ], 403);
    }
}
