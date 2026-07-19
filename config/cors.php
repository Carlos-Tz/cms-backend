<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Any localhost / 127.0.0.1 port so the frontend dev server (Vite, etc.) works without editing this file.
    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // JWT auth uses the Authorization header, not cookies, so credentials aren't needed.
    'supports_credentials' => false,

];
