<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function create()
    {
        $email = $this->maskEmail(auth()->user()->email);

        return view('auth.two-factor', compact('email'));
    }

    public function store(Request $request)
    {
        /**
         * Misschien hier in de toekomst nog valid timestamp veld toevoegen met een bepaalde tijdsduur
         * Dit zou inhouden dat de meer gegegevens gewist moeten worden als 2fa wordt uitgeschakeld
        */ 

        $request->validate([
            'two_factor_code' => 'required', 'integer', 'digits:6',
        ]);

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
        auth()->user()->generateTwoFactorCode();

        auth()->user()->notify(new TwoFactorCodeNotification());

        return redirect()
            ->route('verify.create')
            ->with('success', 'A new code has been sent to your email.');
    }

    private function maskEmail($email)
    {
        $emailparts = explode("@", $email);
        $emailName = substr($emailparts[0], 0, 1);
        $emailName .= str_repeat("*", 8);

        return $emailName."@".$emailparts[1];
    }
}
