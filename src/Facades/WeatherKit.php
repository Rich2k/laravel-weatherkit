<?php
namespace Rich2k\LaravelWeatherKit\Facades;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit location($lat, $lon)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit dataSets(array $dataSets)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit currentAsOf(?Carbon $asOf)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit hourlyStart(?Carbon $hourlyStart)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit hourlyEnd(?Carbon $hourlyEnd)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit dailyStart(?Carbon $dailyStart)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit dailyEnd(?Carbon $dailyEnd)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit timezone(string $timezone)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit language(string $lang)
 * @method static \Rich2k\LaravelWeatherKit\WeatherKit country(string $country)
 * @method static Collection weather()
 * @method static Collection availability()
 * @method static Collection currently()
 * @method static Collection hourly()
 * @method static Collection daily()
 * @method static Collection nextHour()
 */
class WeatherKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'weatherkit'; }

}
