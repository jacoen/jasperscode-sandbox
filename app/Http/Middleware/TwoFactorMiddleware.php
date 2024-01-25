<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($request->routeIs('verify*') && (! $user->two_factor_enabled || ! $user->two_factor_code)) {
                return redirect()->route('home')
                    ->withErrors(['error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.']);
            }

            if ($user->two_factor_enabled && $user->two_factor_code) {
                if (now()->gt($user->two_factor_expires_at)) {
                    $user->resetTwoFactorCode();
                    auth()->logout();
    
                    return redirect()->route('login')
                        ->withErrors(['error' => 'The two factor code has expired. Please login in again.']);
                }

                if (! $request->routeIs('verify*')) {
                    return redirect()->route('verify.create');
                }
            }
        }

        return $next($request);
    }
}
