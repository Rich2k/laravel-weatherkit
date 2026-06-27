<?php

namespace Rich2k\LaravelWeatherKit\Tests;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Rich2k\LaravelWeatherKit\Exceptions\DataSetNotFoundException;
use Rich2k\LaravelWeatherKit\Exceptions\MissingCoordinatesException;
use Rich2k\LaravelWeatherKit\Exceptions\MissingRequiredParametersException;

class WeatherKitTest extends TestCase
{
    public function test_weather_fetches_forecast_with_mocked_weatherkit_response(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse([
                'currentWeather' => ['temperature' => 12.3],
                'forecastDaily' => ['days' => [['conditionCode' => 'Clear']]],
            ]),
        ]);

        $response = $weatherKit
            ->location(51.5074, -0.1278)
            ->country('gb')
            ->dataSets(['currentWeather', 'forecastDaily'])
            ->currentAsOf(Carbon::parse('2024-01-01 10:00:00', 'UTC'))
            ->hourlyStart(Carbon::parse('2024-01-01 11:00:00', 'UTC'))
            ->hourlyEnd(Carbon::parse('2024-01-01 12:00:00', 'UTC'))
            ->dailyStart(Carbon::parse('2024-01-02 00:00:00', 'UTC'))
            ->dailyEnd(Carbon::parse('2024-01-03 00:00:00', 'UTC'))
            ->timezone('Europe/London')
            ->language('en-GB')
            ->weather();

        $this->assertInstanceOf(Collection::class, $response);
        $this->assertSame(12.3, $response->get('currentWeather')->get('temperature'));
        $this->assertSame('Clear', $response->get('forecastDaily')->get('days')->first()->get('conditionCode'));

        $request = $this->history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertSame('https', $request->getUri()->getScheme());
        $this->assertSame('weatherkit.apple.com', $request->getUri()->getHost());
        $this->assertSame('/api/v1/weather/en-GB/51.5074/-0.1278', $request->getUri()->getPath());
        $this->assertSame('Bearer test-jwt-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('currentWeather,forecastDaily', $query['dataSets']);
        $this->assertSame('Europe/London', $query['timezone']);
        $this->assertSame('GB', $query['country']);
        $this->assertSame('2024-01-01T10:00:00Z', $query['currentAsOf']);
        $this->assertSame('2024-01-01T11:00:00Z', $query['hourlyStart']);
        $this->assertSame('2024-01-01T12:00:00Z', $query['hourlyEnd']);
        $this->assertSame('2024-01-02T00:00:00Z', $query['dailyStart']);
        $this->assertSame('2024-01-03T00:00:00Z', $query['dailyEnd']);
    }

    public function test_weather_requires_coordinates_before_making_request(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['currentWeather' => ['temperature' => 12.3]]),
        ]);

        $this->expectException(MissingCoordinatesException::class);

        try {
            $weatherKit->weather();
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    public function test_availability_fetches_available_datasets_with_mocked_response(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['currentWeather', 'forecastHourly']),
        ]);

        $response = $weatherKit->location(51.5074, -0.1278)->availability();

        $this->assertSame(['currentWeather', 'forecastHourly'], $response->all());
        $this->assertSame('/api/v1/availability/51.5074/-0.1278', $this->history[0]['request']->getUri()->getPath());
        $this->assertSame('Bearer test-jwt-token', $this->history[0]['request']->getHeaderLine('Authorization'));
    }

    public function test_helper_methods_return_single_datasets(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['currentWeather' => ['temperature' => 12.3]]),
            $this->jsonResponse(['forecastHourly' => ['hours' => [['temperature' => 13.1]]]]),
            $this->jsonResponse(['forecastDaily' => ['days' => [['conditionCode' => 'Cloudy']]]]),
            $this->jsonResponse(['forecastNextHour' => ['minutes' => [['precipitationChance' => 0.2]]]]),
        ]);

        $weatherKit->location(51.5074, -0.1278);

        $this->assertSame(12.3, $weatherKit->currently()->get('temperature'));
        $this->assertSame(13.1, $weatherKit->hourly()->get('hours')->first()->get('temperature'));
        $this->assertSame('Cloudy', $weatherKit->daily()->get('days')->first()->get('conditionCode'));
        $this->assertSame(0.2, $weatherKit->nextHour()->get('minutes')->first()->get('precipitationChance'));
    }

    public function test_helper_methods_throw_when_dataset_is_missing(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['forecastDaily' => ['days' => []]]),
        ]);

        $this->expectException(DataSetNotFoundException::class);
        $this->expectExceptionMessage('currentWeather data set not available for this location');

        $weatherKit->location(51.5074, -0.1278)->currently();
    }

    public function test_alerts_requires_country_before_making_request(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['weatherAlerts' => []]),
        ]);

        $this->expectException(MissingRequiredParametersException::class);

        try {
            $weatherKit->location(51.5074, -0.1278)->alerts();
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    public function test_alerts_fetches_weather_alerts_when_country_is_set(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['weatherAlerts' => ['alerts' => [['eventText' => 'Wind']]]]),
        ]);

        $response = $weatherKit->location(51.5074, -0.1278)->country('gb')->alerts();
        $request = $this->history[0]['request'];
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertSame('Wind', $response->get('alerts')->first()->get('eventText'));
        $this->assertSame('weatherAlerts', $query['dataSets']);
        $this->assertSame('GB', $query['country']);
    }

    public function test_attribution_fetches_attribution_without_coordinates(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['serviceName' => 'Apple Weather']),
        ]);

        $response = $weatherKit->language('en-GB')->attribution();
        $request = $this->history[0]['request'];

        $this->assertSame('Apple Weather', $response->get('serviceName'));
        $this->assertSame('/attribution/en-GB', $request->getUri()->getPath());
        $this->assertSame('Bearer test-jwt-token', $request->getHeaderLine('Authorization'));
    }

    public function test_attribution_requires_language_before_making_request(): void
    {
        $weatherKit = $this->weatherKitWithMockedResponses([
            $this->jsonResponse(['serviceName' => 'Apple Weather']),
        ]);

        $this->setProtectedProperty($weatherKit, 'lang', null);

        $this->expectException(MissingRequiredParametersException::class);

        try {
            $weatherKit->attribution();
        } finally {
            $this->assertCount(0, $this->history);
        }
    }
}
