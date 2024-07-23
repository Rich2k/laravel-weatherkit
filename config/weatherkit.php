<?php

use Rich2k\LaravelWeatherKit\WeatherKit;

return [
    'auth' => [
        'config' => [
            'jwt' => env('WEATHERKIT_JWT_TOKEN', ''),

            'key' => env('WEATHERKIT_KEY', ''),
            'keyId' => env('WEATHERKIT_KEY_ID', ''),
            'teamId' => env('WEATHERKIT_TEAM_ID'),
            'bundleId' => env('WEATHERKIT_BUNDLE_ID'),
            'tokenTTL' => env('WEATHERKIT_TOKEN_TTL', 3600)
        ],

        /**
         * Can be either 'jwt' to use a pre-generated JWT Token
         * or 'p8' to use your downloaded p8 file from Apple to dynamically generate a JWT Token at runtime
         */
        'type' => env('WEATHERKIT_AUTH_TYPE', WeatherKit::AUTH_TYPE_TOKEN),
    ],

    'languageCode' => env('WEATHERKIT_LANGUAGE_CODE', config('app.locale', 'en')),

    'timezone' => env('WEATHERKIT_TIMEZONE', config('app.timezone', 'UTC')),
];
