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

    /*
    |--------------------------------------------------------------------------
    | Central DMS (Hybrid Deployment)
    |--------------------------------------------------------------------------
    |
    | Used only in hybrid store-and-forward mode. Leave CENTRAL_DMS_URL empty
    | (or unset) for production deployments (this IS the central server) and
    | for fully offline standalone field deployments.
    |
    | When set, ProcessMqttMessage will dispatch SyncRecordToCloud jobs that
    | POST each incoming record to the central server's /api/ingest endpoint,
    | retrying with exponential backoff until delivery is confirmed.
    |
    */
    'central_dms' => [
        'url'   => env('CENTRAL_DMS_URL', ''),
        'token' => env('CENTRAL_DMS_TOKEN', ''),
    ],

];
