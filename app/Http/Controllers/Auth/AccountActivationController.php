<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountActivationRequest;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountActivationController extends Controller
{
    public function create(Request $request): mixed
    {
        $token = $request->route()->parameter('password_token');

        $user = User::where('password_token', $token)->first();

        if (! $user || $user->password_changed_at != null) {
            return redirect()->route('login')
                ->withErrors(['error' => 'The selected token is invalid.']);
        }

        $token_expired = $user->has_token_expired;

        return view('auth.account-activation', compact(['token', 'token_expired']));
    }

    public function store(AccountActivationRequest $request): RedirectResponse
    {
        $user = User::where([
            ['password_token', $request->password_token ],
        ])->first();

        if ($user->has_token_expired) {
            return back()
                ->withErrors(['error' => 'The token has expired, click on the request new token button to request a new token.']);
        }

        if ($user->email !== $request->email) {
            return back()->withErrors(['email' => 'The selected email is invalid.']);
        }

        if ($user->password_changed_at) {
            return redirect()->route('login')
                ->withErrors(['errors' => 'The password has already been changed.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_token' => null,
            'token_expires_at' => null,
            'password_changed_at' => now(),
        ]);

        $user->notify(new AccountActivatedNotification());

        return redirect()->route('login')
            ->with('success', 'Your password has been changed.');
    }
}
