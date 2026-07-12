<?php

return [
    /*
    | The secret must be identical in the admin and base applications in
    | production. APP_KEY is only accepted as a convenience in local/tests.
    */
    'shared_secret' => env('NEWS_PREVIEW_SHARED_SECRET'),
    'allow_app_key_fallback' => env('APP_ENV', 'production') === 'local',

    'cookie_name' => 'rc_news_preview',
    'cookie_domain' => env('NEWS_PREVIEW_COOKIE_DOMAIN', '.regulierungs-check.de'),
    'ttl_minutes' => (int) env('NEWS_PREVIEW_TTL_MINUTES', env('SESSION_LIFETIME', 120)),

    'base_url' => env('NEWS_PREVIEW_BASE_URL', 'https://www.regulierungs-check.de'),
];
