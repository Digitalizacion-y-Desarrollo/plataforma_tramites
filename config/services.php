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

    'accesos' => [
        'login_url' => env('ACCESOS_LOGIN_URL', 'https://accesos.digitalneza.com/api/auth/login'),
        'profile_url' => env('ACCESOS_PROFILE_URL', 'https://accesos.digitalneza.com/api/auth/me'),
        'logout_url' => env('ACCESOS_LOGOUT_URL', 'https://accesos.digitalneza.com/api/auth/logout'),
        'departamentos_url' => env(
            'ACCESOS_DEPARTAMENTOS_URL',
            'https://accesos.digitalneza.com/api/departamentos'
        ),
        'timeout' => (int) env('ACCESOS_TIMEOUT', 15),
        'ca_bundle' => env('ACCESOS_CA_BUNDLE'),
        'system_key' => env('ACCESOS_SYSTEM_KEY'),
    ],

];
