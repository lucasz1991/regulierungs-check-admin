<?php

return [
    /*
    | The secret must be identical in the admin and base applications in
    | production. If it is omitted, both apps may safely fall back to their
    | shared APP_KEY so the preview does not depend on a separate deployment.
    */
    'shared_secret' => env('NEWS_PREVIEW_SHARED_SECRET'),
    'allow_app_key_fallback' => env('NEWS_PREVIEW_ALLOW_APP_KEY_FALLBACK', true),

    'cookie_name' => 'rc_news_preview',
    'cookie_domain' => env('NEWS_PREVIEW_COOKIE_DOMAIN', '.regulierungs-check.de'),
    'ttl_minutes' => (int) env('NEWS_PREVIEW_TTL_MINUTES', env('SESSION_LIFETIME', 120)),

    'base_url' => env('NEWS_PREVIEW_BASE_URL', 'https://www.regulierungs-check.de'),
];
