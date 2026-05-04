<?php

namespace Rich2k\LaravelWeatherKit\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionProperty;
use Rich2k\LaravelWeatherKit\WeatherKit;

abstract class TestCase extends PHPUnitTestCase
{
    protected array $history;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Collection::hasMacro('recursive')) {
            Collection::macro('recursive', function () {
                return $this->map(function ($value) {
                    if (is_array($value) || is_object($value)) {
                        return collect($value)->recursive();
                    }

                    return $value;
                });
            });
        }

        $GLOBALS['weatherkit_test_config']['weatherkit']['auth']['type'] = WeatherKit::AUTH_TYPE_TOKEN;
        $GLOBALS['weatherkit_test_config']['weatherkit']['auth']['config']['jwt'] = 'test-jwt-token';
        $GLOBALS['weatherkit_test_config']['weatherkit']['languageCode'] = 'en';
        $GLOBALS['weatherkit_test_config']['weatherkit']['timezone'] = 'UTC';
    }

    protected function weatherKitWithMockedResponses(array $responses): WeatherKit
    {
        $this->history = [];
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        $weatherKit = new WeatherKit();
        $this->setProtectedProperty($weatherKit, 'client', new Client(['handler' => $stack]));

        return $weatherKit;
    }

    protected function jsonResponse(array $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body));
    }

    protected function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflectionProperty->setAccessible(true);
        }
        $reflectionProperty->setValue($object, $value);
    }
}
