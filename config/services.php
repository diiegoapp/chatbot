<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'zai' => [
        'api_key' => env('ZAI_API_KEY'),
        'base_url' => env('ZAI_BASE_URL', 'https://api.z.ai/api/paas/v4'),
        'model' => env('ZAI_MODEL', 'glm-4.7-flash'),
        'timeout' => (int) env('ZAI_TIMEOUT', 25),
        'connect_timeout' => 5,
        'max_tokens' => (int) env('ZAI_MAX_TOKENS', 1024),
        'thinking' => env('ZAI_THINKING', 'disabled'),
    ],

];
