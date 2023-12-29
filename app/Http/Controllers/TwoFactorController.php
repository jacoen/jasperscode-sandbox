<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Notifications\TwoFactorCodeNotification;
use App\Providers\RouteServiceProvider;
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
        $request->validate([
            'two_factor_code' => 'required', 'integer', 'digits:6',
        ]);

        if ($request->two_factor_code !== auth()->user()->two_factor_code) {
            return redirect()->route('verify.create')->withErrors([
                'two_factor_code' => 'The two factor code you have entered does not match.',
            ]);
        }

        // if ($request->two_factor_expires_at < now()) {
        //     auth()->user()->resetTwoFactorCode();
        //     auth()->logout();
            
        //     return redirect()->route('login')->withErrors([
        //         'error' => 'The two factor code has expired. Please login again.',
        //     ]);
        // }

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
