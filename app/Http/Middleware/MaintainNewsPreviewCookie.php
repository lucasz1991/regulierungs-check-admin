<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\NewsPreviewToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintainNewsPreviewCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if (! $user instanceof User || $user->role !== 'admin' || ! (bool) $user->status) {
            $response->headers->setCookie(NewsPreviewToken::forgetCookie($request));

            return $response;
        }

        $sessionId = $request->hasSession() ? $request->session()->getId() : '';
        $issued = time();
        $token = NewsPreviewToken::issue($user, $sessionId, $issued);

        if ($token === null) {
            $response->headers->setCookie(NewsPreviewToken::forgetCookie($request));

            return $response;
        }

        $response->headers->setCookie(
            NewsPreviewToken::cookie($request, $token, NewsPreviewToken::expiresAt($issued))
        );

        return $response;
    }
}
