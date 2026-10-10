<?php

return [
    'default_base' => env('CURRENCY_DEFAULT_BASE', 'UGX'),

    'provider' => [
        'name' => env('CURRENCY_PROVIDER', 'open_er_api'),
        'url' => env('CURRENCY_PROVIDER_URL', 'https://open.er-api.com/v6/latest/'),
        'api_key' => env('CURRENCY_PROVIDER_API_KEY', ''),
    ],

    'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600),
];
