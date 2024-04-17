<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactorRequest;
use App\Notifications\TwoFactorCodeNotification;

class TwoFactorController extends Controller
{
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
            $attempts = session()->get('two_factor_attempts', 0);

            $attempts++;

            session()->put('two_factor_attempts', $attempts);
            session()->put('last_attempt_time', now()->timestamp);


            if ($attempts > 3 ) {
                auth()->user()->lockUser();
                auth()->logout();
                
                session()->forget(['two_factor_attempts', 'last_attempt_time']);

                return redirect()->route('login')
                    ->withErrors(['error' => 'Too many failed attempts. This account will now be locked for a period of 10 minutes.']);
            }

            return redirect()->route('verify.create')->withErrors([
                'two_factor_code' => 'The two factor code you have entered does not match.',
            ]);
        }

        auth()->user()->resetTwoFactorCode();
        session()->forget(['two_factor_attempts', 'last_attempt_time']);

        return redirect()->route('home');
    }

    public function resend()
    {
        auth()->user()->generateTwoFactorCode();

        auth()->user()->notify(new TwoFactorCodeNotification());

        return redirect()
            ->route('verify.create')
            ->with('success', 'A new code has been sent to your email.');
    }
}
