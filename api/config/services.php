<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        // Required for temporary/STS credentials. The Laravel SES transport reads it
        // from this key; without it an instance profile or a short-lived token pair
        // fails with an opaque auth error rather than an obvious missing-token one.
        'token' => env('AWS_SESSION_TOKEN'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        // When true, no static credentials are sent at all and the SDK resolves them
        // from the instance/task role. This is the correct setting in real production;
        // static keys in .env are a fallback for local and short-lived environments.
        'use_iam_role' => (bool) env('AWS_USE_IAM_ROLE', false),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'demo'),
        'demo_mode' => filter_var(env('WHATSAPP_DEMO_MODE', true), FILTER_VALIDATE_BOOLEAN),
        'business_phone' => env('WHATSAPP_BUSINESS_PHONE', '+256731794401'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', 'demo_phone_number_id'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', 'demo_access_token'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'demo_verify_token'),
        'default_template' => env('WHATSAPP_DEFAULT_TEMPLATE', 'demo_business_alert'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
