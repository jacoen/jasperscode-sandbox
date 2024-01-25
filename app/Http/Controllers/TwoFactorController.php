<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactorRequest;
use App\Notifications\TwoFactorCodeNotification;

class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($this->checkTwoFactor()) return $this->checkTwoFactor();
            return $next($request);
        });
    }

    public function create()
    {
        $email = maskEmail(auth()->user()->email);

        return view('auth.two-factor', compact('email'));
    }

    public function store(TwoFactorRequest $request)
    {
        /**
         * Misschien hier in de toekomst nog valid timestamp veld toevoegen met een bepaalde tijdsduur
         * Dit zou inhouden dat de meer gegegevens gewist moeten worden als 2fa wordt uitgeschakeld
        */

        if ($request->two_factor_code !== auth()->user()->two_factor_code) {
            return redirect()->route('verify.create')->withErrors([
                'two_factor_code' => 'The two factor code you have entered does not match.',
            ]);
        }

        auth()->user()->resetTwoFactorCode();

        return redirect()->route('home');
    }

    public function resend()
    {
        if (! auth()->user()->two_factor_enabled || ! auth()->user()->two_factor_code) {
            return redirect(route('home'))
                ->withErrors(['error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.']);
        }

        auth()->user()->generateTwoFactorCode();

        auth()->user()->notify(new TwoFactorCodeNotification());

        return redirect()
            ->route('verify.create')
            ->with('success', 'A new code has been sent to your email.');
    }

    private function checkTwoFactor()
    {
        if (! auth()->user()->two_factor_enabled || ! auth()->user()->two_factor_code) {
            return redirect(route('home'))
                ->withErrors(['error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.']);
        }

        return null;
    }
}
