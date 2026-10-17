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

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],

    // FRONTEND_URL is the origin the UI actually runs on. Local Vite is
    // http://localhost:5173 while CORS_ALLOWED_ORIGINS defaults to the hosted UI,
    // so a browser on the dev server was refused every response — including
    // /users/me — and the app looked signed out. The hosted origin stays allowed.
    'allowed_origins' => array_values(array_unique(array_filter(array_map(
        static fn (string $origin) => rtrim(trim($origin), '/'),
        [
            ...explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://afroduuka-ui.onrender.com')),
            (string) env('FRONTEND_URL', ''),
            // Dev publishes the UI through nginx on port 80. The browser origin is
            // http://localhost, while FRONTEND_URL is the Vite port inside the
            // container. These are not added outside local, so production stays
            // limited to the hosted UI.
            ...(env('APP_ENV') === 'local' ? ['http://localhost', 'http://127.0.0.1'] : []),
        ]
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Request-Id'],

    'exposed_headers' => ['X-Request-Id'],

    'max_age' => 0,

    'supports_credentials' => false,

];
