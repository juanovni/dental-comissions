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
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v19.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_phone' => env('WHATSAPP_BUSINESS_PHONE'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'dental-commissions-verify'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
    ],

    'meta' => [
        'api_url' => env('META_GRAPH_API_URL', 'https://graph.facebook.com/v25.0'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'verify_token' => env('META_VERIFY_TOKEN', 'dental-social-verify'),
        'sync_days' => (int) env('META_SYNC_DAYS', 30),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'request_timeout' => (int) env('GEMINI_REQUEST_TIMEOUT', 30),
    ],

];
