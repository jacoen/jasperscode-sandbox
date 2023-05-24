<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewTokenRequest;
use App\Models\User;
use App\Notifications\NewTokenRequestedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestNewTokenController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $token = $request->route()->parameter('password_token');

        $user = User::where('password_token', $token)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'error' => 'Invalid token.',
            ]);
        }

        if (! $user->has_token_expired) {
            return redirect()->route('activate-account.create', $token)
                ->withErrors(['error' => 'The current token has not expired yet.']);
        }

        return view('request-token', compact('token'));
    }

    public function store(StoreNewTokenRequest $request)
    {
        $user = User::where('password_token', $request->token)->first();

        if ($user->email !== $request->email) {
            return redirect()->route('request-token.create', $request->token)
                ->withErrors(['email' => 'The selected email is invalid.']);
        }

        if (! $user->hasTokenExpired) {
            return redirect()->route('request-token.create', $request->token)
                ->withErrors(['error' => 'This token has not yet expired.']);
        }

        $user->generatePasswordToken();
        $user->notify(new NewTokenRequestedNotification());

        return redirect()->route('login')->with('success', 'You have requested a new token. Please check your mail for the new token.');
    }
}
