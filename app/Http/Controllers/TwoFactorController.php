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
        $user = auth()->user();

        if ($user->last_attempt_at && $user->last_attempt_at->lt(now()->subMinutes(5)) && $user->two_factor_attempts >= 1) {
            $user->resetTwoFactorAttempts();
        }

        if ($request->two_factor_code !== $user->two_factor_code) {
            $this->incrementAttempt($user);

            if ($user->two_factor_attempts >= 5 ) {
                return $this->handleExceededAttempts($user);
            }

            return redirect()->route('verify.create')->withErrors([
                'two_factor_code' => 'The two factor code you have entered does not match.',
            ]);
        }
        
        if ($user->two_factor_attempts >= 1 && $user->last_attempt_at !== null) {
            $user->resetTwoFactorAttempts();
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

    private function incrementAttempt($user)
    {
        $user->increment('two_factor_attempts');

        $user->timestamps = false;
        $user->update(['last_attempt_at' => now()]);
        $user->timestamps = true;
    }

    private function handleExceededAttempts($user)
    {
        auth()->user()->lockUser();
        auth()->user()->resetTwoFactorCode();
        auth()->logout();

        return redirect()->route('login')
            ->withErrors(['error' => 'Too many failed attempts. This account will now be locked for a period of 10 minutes.']);
    }
}
