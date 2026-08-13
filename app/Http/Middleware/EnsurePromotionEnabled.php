<?php

namespace App\Http\Middleware;

use App\Services\Promotion\PromotionSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePromotionEnabled
{
    public function __construct(private readonly PromotionSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->settings->isEnabled(), 503, 'Die Promotion ist sicherheitsbedingt nicht freigeschaltet.');

        return $next($request);
    }
}
