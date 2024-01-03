<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return view('auth.profile');
    }

    public function update(ProfileUpdateRequest $request)
    {
        if ($request->password) {
            auth()->user()->update(['password' => Hash::make($request->password)]);
        }

        auth()->user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profile updated.');
    }

    public function twoFactorSettings(Request $request)
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Admin', 'Super Admin']) && $request->input('two_factor_enabled') === null) {
            return redirect()->route('profile.show')->withErrors([
                'two_factor_enabled' => 'You cannot disable the two factor authentication'
            ]);
        }

        $user->update([
            'two_factor_enabled' => ! $user->two_factor_enabled,
        ]);

        if ($user->two_factor_enabled) {
            auth()->logout();
            
            return redirect()->route('login')
                ->with('success', 'Two factor authentication has been enabled. Please sign in again.');
        }

        return redirect()->route('profile.show')
            ->with('success', 'The two factor authentication has been disabled.');
    }
}
