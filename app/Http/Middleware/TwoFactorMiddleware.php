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

            if ($user->two_factor_enabled && $user->two_factor_code) {
                if (! $request->routeIs('verify*')) {
                    return redirect()->route('verify.create');
                }
    
                if ($user->two_factor_expires_at->lt(now())) {
                    $user->resetTwoFactorCode();
                    auth()->logout();
    
                    return redirect()->route('login')
                        ->withErrors(['error' => 'The two factor code has expired. Please login in again.']);
                }
            } elseif ($request->routeIs('verify*')) {
                return redirect(route('home'))
                    ->withErrors(['error' => 'Could not verify your two factor because you have not enabled two factor authentication or you have no two factor code.']);
            }
        }
        return $next($request);
    }
}
