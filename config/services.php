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

    /*
    | The PostgREST-facing project — scheme+host only, and the publishable
    | (anon) key — that kiosk licences point at. Distinct from the
    | SUPABASE_DB_* connection above, which is the direct Postgres link this
    | application itself uses.
    */
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'publishable_key' => env('SUPABASE_PUBLISHABLE_KEY'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    | The Discord bot that lets members clock in/out with /clock in this
    | organization's Discord server. public_key verifies each interaction
    | request came from Discord; bot_token and application_id are used to
    | register the /clock command (see DiscordRegisterCommands).
    */
    'discord' => [
        'public_key' => env('DISCORD_PUBLIC_KEY'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'application_id' => env('DISCORD_APPLICATION_ID'),
    ],

];
