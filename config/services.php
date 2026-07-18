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
        'graph_version' => env('META_GRAPH_VERSION', 'v25.0'),
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'redirect_uri' => env('META_REDIRECT_URI'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'page_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('META_PAGE_IDS', ''))))),
        'verify_token' => env('META_VERIFY_TOKEN', 'dental-social-verify'),
        'sync_days' => (int) env('META_SYNC_DAYS', 30),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
        'request_timeout' => (int) env('OPENAI_REQUEST_TIMEOUT', 30),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'request_timeout' => (int) env('GEMINI_REQUEST_TIMEOUT', 30),
    ],

    'voice' => [
        'tool_token' => env('VOICE_TOOL_TOKEN'),
    ],

    'google_oauth' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI'),
        'scopes' => [
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/userinfo.email',
        ],
        'access_type' => 'offline',
        'prompt' => 'consent',
    ],

];
