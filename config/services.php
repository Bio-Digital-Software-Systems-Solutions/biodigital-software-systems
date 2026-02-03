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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    | Twilio SMS & WhatsApp Service
    |--------------------------------------------------------------------------
    |
    | Credentials for Twilio SMS and WhatsApp messaging service.
    | Used for pastoral care appointment reminders and notifications.
    |
    */

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Integration Settings
    |--------------------------------------------------------------------------
    |
    | Global SMS notification settings for the application.
    |
    */

    'sms' => [
        'enabled' => env('SMS_INTEGRATION_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'twilio'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Integration Settings
    |--------------------------------------------------------------------------
    |
    | Global WhatsApp notification settings for the application.
    |
    */

    'whatsapp' => [
        'enabled' => env('WHATSAPP_INTEGRATION_ENABLED', false),
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Telegram Bot API used for appointment reminders.
    | Create a bot via @BotFather on Telegram to get your bot token.
    |
    */

    'telegram' => [
        'enabled' => env('TELEGRAM_INTEGRATION_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

];
