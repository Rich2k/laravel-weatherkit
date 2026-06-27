<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestCase.php';

if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        $config = $GLOBALS['weatherkit_test_config'] ?? [];

        if ($key === null) {
            return $config;
        }

        if (is_array($key)) {
            $GLOBALS['weatherkit_test_config'] = array_replace_recursive($config, $key);
            return null;
        }

        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

$GLOBALS['weatherkit_test_config'] = [
    'weatherkit' => [
        'auth' => [
            'type' => \Rich2k\LaravelWeatherKit\WeatherKit::AUTH_TYPE_TOKEN,
            'config' => [
                'jwt' => 'test-jwt-token',
                'key' => '',
                'keyId' => '',
                'teamId' => '',
                'bundleId' => '',
                'tokenTTL' => 3600,
            ],
        ],
        'languageCode' => 'en',
        'timezone' => 'UTC',
    ],
];
