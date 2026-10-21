<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_DEFAULT_NOTIFICATION_CHANNEL'),
        ],
    ],

    'sunat' => [
        'client_id' => env('SUNAT_CLIENT_ID'),
        'client_secret' => env('SUNAT_CLIENT_SECRET'),
        'ruc' => env('SUNAT_RUC'),
        'sol_user' => env('SUNAT_SOL_USER'),
        'sol_password' => env('SUNAT_SOL_PASSWORD'),
        'sol_principal_user' => env('SUNAT_SOL_PRINCIPAL_USER'),
        'sol_principal_password' => env('SUNAT_SOL_PRINCIPAL_PASSWORD'),
        'oauth_url' => env('SUNAT_OAUTH_URL', 'https://api-seguridad.sunat.gob.pe'),
        'sire_base_url' => env('SUNAT_SIRE_BASE_URL', 'https://api-sire.sunat.gob.pe'),
        'cpe_base_url' => env('SUNAT_CPE_BASE_URL', 'https://api-cpe.sunat.gob.pe'),
        'scope_sire' => 'https://api-sire.sunat.gob.pe',
        'scope_cpe' => 'https://api-cpe.sunat.gob.pe',
    ],

    'jsonpe' => [
        'token' => env('JSONPE_TOKEN'),
        'base_url' => env('JSONPE_BASE_URL', 'https://api.json.pe/api'),
        'timeout' => (int) env('JSONPE_TIMEOUT', 120),
    ],

];
