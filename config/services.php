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

    // API de inscripciones del CRM. Sin token solo mientras se define con el
    // cliente como se autenticara; por defecto (sin la variable) lo exige.
    'crm' => [
        'requiere_token' => (bool) env('CRM_API_REQUIERE_TOKEN', true),
        // Solo para pruebas: si tiene valor, todo alumno nuevo recibe esta
        // contrasena (sin cambio obligatorio). Vacio = una al azar por alumno.
        'password_prueba' => env('CRM_PASSWORD_PRUEBA'),
    ],

];
