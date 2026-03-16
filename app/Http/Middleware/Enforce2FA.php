<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Enforce2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $requiredRoles = config('security.two_factor.required_roles', []);

        if (! in_array($user->role, $requiredRoles, true)) {
            return $next($request);
        }

        // If 2FA not set up, redirect to setup
        if (! $user->two_factor_confirmed_at) {
            if (! $request->routeIs('2fa.*')) {
                return redirect()->route('2fa.setup');
            }

            return $next($request);
        }

        // If 2FA set up but not verified this session
        if (! session('2fa_verified')) {
            if (! $request->routeIs('2fa.*')) {
                return redirect()->route('2fa.verify');
            }
        }

        return $next($request);
    }
}
