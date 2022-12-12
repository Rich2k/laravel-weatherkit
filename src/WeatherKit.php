<?php
namespace Rich2k\LaravelWeatherKit;

use Illuminate\Support\Arr;
use Carbon\Carbon;

class WeatherKit
{
    protected $jwttoken;
    protected $weatherEndpoint = 'https://weatherkit.apple.com/api/v1/weather';
    protected $availabilityEndpoint = 'https://weatherkit.apple.com/api/v1/availability';
    protected $lat;
    protected $lon;
    protected $country = null;
    protected $lang = 'en_US';
    protected $params = [];
    protected $client;
    protected $dataSets = ['currentWeather', 'forecastDaily', 'forecastHourly', 'forecastNextHour'];

    /**
     * WeatherKit constructor.
     */
    public function __construct()
    {
        $this->jwttoken = config('weatherkit.jwttoken');
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Sets the latitude and longitude. Must be set
     *
     * @param $lat
     * @param $lon
     * @return $this
     */
    public function location($lat, $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
        return $this;
    }

    /**
     * Builds the endpoint url and sends the get request
     *
     * @return mixed
     */
    public function get()
    {
        $url = $this->weatherEndpoint  . '/' . $this->lang . '/' . $this->lat . '/' . $this->lon;

        if (! Arr::has($this->params, 'dataSets')) {
            $this->params['dataSets'] = implode(',', $this->dataSets);
        }

        $response = $this->client->get($url, [
           'headers' => ['Authorization' => 'Bearer ' . $this->jwttoken],
           'query' => $this->params,
        ]);

        return json_decode($response->getBody());
    }

    /**
     * Builds the endpoint url and sends the get request
     *
     * @return mixed
     */
    public function availability()
    {
        $url = $this->availabilityEndpoint  . '/' . $this->lat . '/' . $this->lon;

        $response = $this->client->get($url, [
            'headers' => ['Authorization' => 'Bearer ' . $this->jwttoken],
            'query' => $this->params,
        ]);

        return $this->dataSets = json_decode($response->getBody());
    }

    /**
     * Sets the dataSets query parameter by taking an array
     *
     * @param $dataSets
     * @return $this
     */
    public function dataSets($dataSets): WeatherKit
    {
        $this->dataSets = $dataSets;
        return $this;
    }

    /**
     * The time to obtain current conditions. Defaults to now.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $asOf
     * @return $this
     */
    public function currentAsOf(?Carbon $asOf = null): WeatherKit
    {
        if (! $asOf && Arr::has($this->params, 'currentAsOf')) {
            unset($this->params['currentAsOf']);
            return $this;
        }

        $this->params['currentAsOf'] = $asOf->toIso8601ZuluString();
        return $this;
    }

    /**
     * The time to start the hourly forecast. If this parameter is absent, hourly forecasts start on the current hour.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $asOf
     * @return $this
     */
    public function hourlyStart(?Carbon $hourlyStart = null): WeatherKit
    {
        if (! $hourlyStart && Arr::has($this->params, 'hourlyStart')) {
            unset($this->params['hourlyStart']);
            return $this;
        }

        $this->params['hourlyStart'] = $hourlyStart->toIso8601ZuluString();
        return $this;
    }

    /**
     * The time to end the hourly forecast. If this parameter is absent, hourly forecasts run 24 hours or the length of the daily forecast, whichever is longer.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $asOf
     * @return $this
     */
    public function hourlyEnd(?Carbon $hourlyEnd = null): WeatherKit
    {
        if (! $hourlyEnd && Arr::has($this->params, 'hourlyEnd')) {
            unset($this->params['hourlyEnd']);
            return $this;
        }

        $this->params['hourlyEnd'] = $hourlyEnd->toIso8601ZuluString();
        return $this;
    }

    /**
     * The time to start the daily forecast. If this parameter is absent, daily forecasts start on the current day.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $asOf
     * @return $this
     */
    public function dailyStart(?Carbon $dailyStart = null): WeatherKit
    {
        if (! $dailyStart && Arr::has($this->params, 'dailyStart')) {
            unset($this->params['dailyStart']);
            return $this;
        }

        $this->params['dailyStart'] = $dailyStart->toIso8601ZuluString();
        return $this;
    }

    /**
     * The time to end the daily forecast. If this parameter is absent, daily forecasts run for 10 days.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $asOf
     * @return $this
     */
    public function dailyEnd(?Carbon $dailyEnd = null): WeatherKit
    {
        if (! $dailyEnd && Arr::has($this->params, 'dailyEnd')) {
            unset($this->params['dailyEnd']);
            return $this;
        }

        $this->params['dailyEnd'] = $dailyEnd->toIso8601ZuluString();
        return $this;
    }

    /**
     * The name of the timezone to use for rolling up weather forecasts into daily forecasts.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param string $timezone
     * @return $this
     */
    public function timezone(string $timezone): WeatherKit
    {
        $this->params['timezone'] = $timezone;
        return $this;
    }

    /**
     * Sets the return language
     *
     * @param $lang
     * @return $this
     */
    public function language(string $lang): WeatherKit
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function country(string $country): WeatherKit
    {
        $this->country = $country;
        return $this;
    }

    ///////////////////////////////////////////////////////////////////
    //////////////////////////// HELPERS //////////////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * Filters out metadata to get only currently
     *
     * @return $this
     */
    public function currently()
    {
        return $this->dataSets(['currentWeather'])->get()->currentWeather;
    }

    /**
     * Filters out metadata to get only hourly
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->dataSets(['forecastHourly'])->get()->forecastHourly;
    }

    /**
     * Filters out metadata to get only daily
     *
     * @return $this
     */
    public function daily()
    {
        return $this->dataSets(['forecastDaily'])->get()->forecastDaily;
    }

    /**
     * Filters out metadata to get only next hour
     *
     * @return $this
     */
    public function nextHour()
    {
        return $this->dataSets(['forecastNextHour'])->get()->forecastNextHour;
    }
}
