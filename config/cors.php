<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list of deployed frontend origins, per environment.
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))
    ),

    // Any localhost / 127.0.0.1 port so the frontend dev server (Vite, etc.) works without editing this file.

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // JWT auth uses the Authorization header, not cookies, so credentials aren't needed.
    'supports_credentials' => false,

];
