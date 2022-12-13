<?php
return [
    'auth' => [
        'config' => [
            'jwt' => env('WEATHERKIT_JWT_TOKEN', ''),
        ],

        'type' => 'jwt',
    ],

    'languageCode' => env('WEATHERKIT_LANGUAGE_CODE', config('app.locale', 'en')),

    'timezone' => env('WEATHERKIT_TIMEZONE', config('app.timezone', 'UTC')),
];
