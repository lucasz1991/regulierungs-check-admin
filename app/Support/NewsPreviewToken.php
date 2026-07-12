<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpFoundation\Cookie;

final class NewsPreviewToken
{
    /**
     * Issue a short-lived, signed proof for the current admin session.
     */
    public static function issue(User $user, string $sessionId, ?int $now = null): ?string
    {
        $secret = self::secret();

        if ($secret === null || $sessionId === '') {
            return null;
        }

        $issued = $now ?? time();
        $ttlSeconds = max(60, (int) config('news-preview.ttl_minutes', 120) * 60);
        $payload = [
            'admin_user_id' => (int) $user->getKey(),
            'admin_session_id' => $sessionId,
            'issued' => $issued,
            'expires' => $issued + $ttlSeconds,
        ];

        try {
            $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return null;
        }

        $signature = hash_hmac('sha256', $encodedPayload, $secret, true);

        return $encodedPayload.'.'.self::base64UrlEncode($signature);
    }

    public static function cookie(Request $request, string $token, int $expires): Cookie
    {
        return new Cookie(
            name: (string) config('news-preview.cookie_name', 'rc_news_preview'),
            value: $token,
            expire: $expires,
            path: '/',
            domain: self::cookieDomain($request),
            secure: $request->isSecure(),
            httpOnly: true,
            raw: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    public static function forgetCookie(Request $request): Cookie
    {
        return new Cookie(
            name: (string) config('news-preview.cookie_name', 'rc_news_preview'),
            value: '',
            expire: time() - 3600,
            path: '/',
            domain: self::cookieDomain($request),
            secure: $request->isSecure(),
            httpOnly: true,
            raw: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    public static function expiresAt(?int $now = null): int
    {
        return ($now ?? time()) + max(60, (int) config('news-preview.ttl_minutes', 120) * 60);
    }

    public static function cookieDomain(Request $request): ?string
    {
        $configuredDomain = strtolower(trim((string) config('news-preview.cookie_domain', '.regulierungs-check.de')));
        $bareDomain = ltrim($configuredDomain, '.');

        if ($bareDomain === '') {
            return null;
        }

        $host = strtolower($request->getHost());

        if ($host !== $bareDomain && ! str_ends_with($host, '.'.$bareDomain)) {
            return null;
        }

        return '.'.$bareDomain;
    }

    private static function secret(): ?string
    {
        $secret = trim((string) config('news-preview.shared_secret'));

        if ($secret !== '') {
            return $secret;
        }

        if (! config('news-preview.allow_app_key_fallback', false) && ! app()->environment('testing')) {
            return null;
        }

        $appKey = trim((string) config('app.key'));

        return $appKey !== '' ? $appKey : null;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
