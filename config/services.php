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

    'ffmpeg' => [
        'bin' => env('FFMPEG_BIN', 'ffmpeg'),
        'enable_gpu_transcoding' => env('ENABLE_GPU_TRANSCODING', true),
        // auto | nvidia | amd | software — auto picks NVENC, then VAAPI, then libx265
        'hw_mode' => env('TRANSCODE_HW_MODE', 'auto'),
        'vaapi_device' => env('VAAPI_DEVICE', '/dev/dri/renderD128'),
        'video_filter' => env('FFMPEG_VIDEO_FILTER'), // null = tonemap HDR sources, leave SDR untouched
    ],

    'media_servers' => [
        // Shared secret for /webhooks/{jellyfin,plex,emby}; sent as X-Flowarr-Token or ?token=
        'webhook_token' => env('MEDIA_SERVER_WEBHOOK_TOKEN', env('JELLYFIN_WEBHOOK_TOKEN')),
    ],

];
