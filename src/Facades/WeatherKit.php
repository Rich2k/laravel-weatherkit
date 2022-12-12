<?php
namespace Rich2k\LaravelWeatherKit\Facades;

use Illuminate\Support\Facades\Facade;

class WeatherKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'weatherkit'; }

}
