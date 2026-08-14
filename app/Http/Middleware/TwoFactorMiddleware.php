<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->two_factor_enabled && $user->two_factor_secret) {
            if (!$request->session()->has('2fa_verified')) {
                return redirect()->route('2fa.verify');
            }
        }

        return $next($request);
    }
}
