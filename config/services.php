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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'meash_cleaning_solution_bot'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/api/telegram/webhook' : null),
        'miniapp_url' => env('TELEGRAM_MINIAPP_URL', env('APP_URL') ? rtrim(env('APP_URL'), '/') . '/telegram-miniapp' : null),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // Admin's own Telegram chat id (for "Send test" — set in Render
        // dashboard as TELEGRAM_ADMIN_CHAT_ID; get it by messaging the
        // bot once and reading chat.id). Never fall back to a customer.
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    ],

    // Cloudinary image uploads for the admin Photos page. Credentials
    // come ONLY from environment variables — never hardcoded. Either
    // the all-in-one CLOUDINARY_URL or the three separate keys.
    'cloudinary' => [
        'url' => env('CLOUDINARY_URL'),
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

];
