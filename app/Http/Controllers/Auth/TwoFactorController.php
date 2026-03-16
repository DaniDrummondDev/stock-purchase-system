<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function setup(Request $request): View
    {
        $google2fa = new Google2FA;
        $user = $request->user();

        if (! $user->two_factor_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->two_factor_secret = Crypt::encryptString($secret);
            $user->save();
        } else {
            $secret = Crypt::decryptString($user->two_factor_secret);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'SPS'),
            $user->email,
            $secret,
        );

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $google2fa = new Google2FA;
        $user = $request->user();
        $secret = Crypt::decryptString($user->two_factor_secret);

        if (! $google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido. Tente novamente.']);
        }

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = Crypt::encryptString(
            json_encode($this->generateRecoveryCodes())
        );
        $user->save();

        return redirect()->route('dashboard')->with('status', '2FA ativado com sucesso!');
    }

    public function verify(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return view('auth.two-factor-verify');
        }

        $request->validate(['code' => 'required|string']);

        $google2fa = new Google2FA;
        $user = $request->user();
        $secret = Crypt::decryptString($user->two_factor_secret);

        if ($google2fa->verifyKey($secret, $request->code, config('security.two_factor.window', 1))) {
            session(['2fa_verified' => true]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'Código 2FA inválido.']);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $google2fa = new Google2FA;
        $user = $request->user();
        $secret = Crypt::decryptString($user->two_factor_secret);

        if (! $google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->back()->with('status', '2FA desativado.');
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        $count = config('security.two_factor.recovery_codes', 8);

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10);
        }

        return $codes;
    }
}
