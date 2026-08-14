<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    public function showVerifyForm()
    {
        return view('auth.2fa_verify');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $google2fa = new Google2FA();
        $user = Auth::user();

        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($valid) {
            $request->session()->put('2fa_verified', true);
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['code' => 'Invalid authentication code.']);
    }

    public function showSetupForm()
    {
        $user = Auth::user();
        $google2fa = new Google2FA();

        if (!$user->two_factor_secret) {
            $secret = $google2fa->generateSecretKey();
            $request = request();
            $request->session()->put('2fa_secret', $secret);
        } else {
            $secret = $user->two_factor_secret;
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeImage = $writer->writeString($qrCodeUrl);

        return view('auth.2fa_setup', compact('qrCodeImage', 'secret'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $google2fa = new Google2FA();
        $secret = $request->session()->get('2fa_secret');

        // If user already has a secret (re-enabling), use that
        if (!$secret && Auth::user()->two_factor_secret) {
            $secret = Auth::user()->two_factor_secret;
        }

        $valid = $google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            $user = Auth::user();
            $user->two_factor_secret = $secret;
            $user->two_factor_enabled = true;
            $user->save();

            $request->session()->forget('2fa_secret');
            $request->session()->put('2fa_verified', true);

            return redirect()->route('dashboard')->with('success', 'احراز هویت دو مرحله‌ای فعال شد.');
        }

        return back()->withErrors(['code' => 'Invalid verification code.']);
    }

    public function disable()
    {
        $user = Auth::user();
        $user->two_factor_enabled = false;
        // We keep the secret so they don't have to rescan if they re-enable
        // $user->two_factor_secret = null;
        $user->save();

        request()->session()->forget('2fa_verified');

        return back()->with('success', 'احراز هویت دو مرحله‌ای غیرفعال شد.');
    }
}
