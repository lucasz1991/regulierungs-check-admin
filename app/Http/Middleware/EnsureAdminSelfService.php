<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSelfService
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Fortify's guest login/reset flows and verification endpoint must
        // remain reachable. Once an authenticated account hits any generic
        // self-service endpoint, only a global admin is allowed through.
        if ($user === null || $request->routeIs(
            'login',
            'login.store',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'two-factor.login',
            'two-factor.login.store',
        )) {
            return $next($request);
        }

        abort_unless(
            $user->isActive() && $user->isAdmin(),
            403,
        );

        return $next($request);
    }
}
