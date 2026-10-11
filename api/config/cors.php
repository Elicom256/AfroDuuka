<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API auth is bearer-token based (localStorage), not cookie based, so
    | credentials are never attached automatically. Still, allow-list the exact
    | frontend origins instead of shipping Laravel's permissive "*" default.
    |
    | Comma-separate multiple origins in the CORS_ALLOWED_ORIGINS env var;
    | a custom domain for the UI can be added there without a redeploy.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://afroduuka-ui.onrender.com'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
