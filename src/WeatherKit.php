<?php
namespace Rich2k\LaravelWeatherKit;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Rich2k\LaravelWeatherKit\Exceptions\DataSetNotFoundException;
use Rich2k\LaravelWeatherKit\Exceptions\LaravelWeatherKitException;
use Rich2k\LaravelWeatherKit\Exceptions\KeyNotFoundExceptione;
use Rich2k\LaravelWeatherKit\Exceptions\MissingCoordinatesException;
use Rich2k\LaravelWeatherKit\Exceptions\TokenGenerationFailedException;

class WeatherKit
{
    public const AUTH_TYPE_TOKEN = 'jwt';
    public const AUTH_TYPE_P8 = 'p8';

    protected $client;

    protected $jwtToken;

    protected $weatherEndpoint = 'https://weatherkit.apple.com/api/v1/weather';
    protected $availabilityEndpoint = 'https://weatherkit.apple.com/api/v1/availability';

    protected array $params = [];
    protected array $dataSets = ['currentWeather', 'forecastDaily', 'forecastHourly', 'forecastNextHour'];
    protected ?float $lat = null;
    protected ?float $lon = null;
    protected ?string $country = null;
    protected ?string $lang = null;
    protected ?Carbon $currentAsOf = null;
    protected ?Carbon $hourlyStart = null;
    protected ?Carbon $hourlyEnd = null;
    protected ?Carbon $dailyStart = null;
    protected ?Carbon $dailyEnd = null;

    /**
     * WeatherKit constructor.
     *
     * @throws LaravelWeatherKitException
     */
    public function __construct()
    {
        if (config('weatherkit.auth.type') === WeatherKit::AUTH_TYPE_P8) {
            try {
                $this->jwtToken = new JWTToken(
                    config('weatherkit.auth.config.pathToKeyFile'),
                    config('weatherkit.auth.config.keyId'),
                    config('weatherkit.auth.config.teamId'),
                    config('weatherkit.auth.config.bundleId'),
                    config('weatherkit.auth.config.tokenTTL')
                );
            } catch (TokenGenerationFailedException | KeyFileMissingException | TokenGenerationFailedException $e) {
                throw new LaravelWeatherKitException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $this->jwtToken = config('weatherkit.auth.config.jwt');
        }

        $this->client = new \GuzzleHttp\Client();

        $this->language(config('weatherkit.languageCode'));
        $this->timezone(config('weatherkit.timezone'));
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
     * @return Collection
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function weather(): Collection
    {
        if (! $this->lat || ! $this->lon) {
            throw new MissingCoordinatesException('Missing coordinates of either latitude or longitude.');
        }

        $url = $this->weatherEndpoint  . '/' . $this->lang . '/' . $this->lat . '/' . $this->lon;

        $response = $this->client->get($url, [
           'headers' => ['Authorization' => 'Bearer ' . $this->jwtToken],
           'query' => $this->buildParams(),
        ]);

        return collect(json_decode($response->getBody()))->recursive();
    }

    /**
     * Builds the endpoint url and sends the get request
     *
     * @return Collection
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function availability(): Collection
    {
        if (! $this->lat || ! $this->lon) {
            throw new MissingCoordinatesException('Missing coordinates of either latitude or longitude.');
        }

        $url = $this->availabilityEndpoint  . '/' . $this->lat . '/' . $this->lon;

        $response = $this->client->get($url, [
            'headers' => ['Authorization' => 'Bearer ' . $this->jwtToken],
            'query' => $this->buildParams(),
        ]);

        $this->dataSets = json_decode($response->getBody());

        return collect($this->dataSets);
    }

    /**
     * Sets the dataSets query parameter by taking an array
     *
     * @param array $dataSets
     * @return $this
     */
    public function dataSets(array $dataSets): WeatherKit
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
    public function currentAsOf(?Carbon $asOf): WeatherKit
    {
        $this->currentAsOf = $asOf;
        return $this;
    }

    /**
     * The time to start the hourly forecast. If this parameter is absent, hourly forecasts start on the current hour.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $hourlyStart
     * @return $this
     */
    public function hourlyStart(?Carbon $hourlyStart): WeatherKit
    {
        $this->hourlyStart = $hourlyStart;
        return $this;
    }

    /**
     * The time to end the hourly forecast. If this parameter is absent, hourly forecasts run 24 hours or the length of the daily forecast, whichever is longer.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $hourlyEnd
     * @return $this
     */
    public function hourlyEnd(?Carbon $hourlyEnd): WeatherKit
    {
        $this->hourlyEnd = $hourlyEnd;
        return $this;
    }

    /**
     * The time to start the daily forecast. If this parameter is absent, daily forecasts start on the current day.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $dailyStart
     * @return $this
     */
    public function dailyStart(?Carbon $dailyStart): WeatherKit
    {
        $this->dailyStart = $dailyStart;
        return $this;
    }

    /**
     * The time to end the daily forecast. If this parameter is absent, daily forecasts run for 10 days.
     * See: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude
     *
     * @param Carbon|null $dailyEnd
     * @return $this
     */
    public function dailyEnd(?Carbon $dailyEnd): WeatherKit
    {
        $this->dailyEnd = $dailyEnd;
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
        $this->timezone = $timezone;
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
     * @return Collection
     * @throws DataSetNotFoundException
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function currently(): Collection
    {
        return $this->getSingleDataSet('currentWeather');
    }

    /**
     * Filters out metadata to get only hourly
     *
     * @return Collection
     * @throws DataSetNotFoundException
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function hourly(): Collection
    {
        return $this->getSingleDataSet('forecastHourly');
    }

    /**
     * Filters out metadata to get only daily
     *
     * @return Collection
     * @throws DataSetNotFoundException
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function daily(): Collection
    {
        return $this->getSingleDataSet('forecastDaily');
    }

    /**
     * Filters out metadata to get only next hour
     *
     * @return Collection
     * @throws DataSetNotFoundException
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function nextHour(): Collection
    {
        return $this->getSingleDataSet('forecastNextHour');
    }

    /**
     * @param string $dataSet
     * @return Collection
     * @throws DataSetNotFoundException
     * @throws MissingCoordinatesException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getSingleDataSet(string $dataSet)
    {
        $response = $this->dataSets([$dataSet])->weather();

        if (! $response->has($dataSet)) {
            throw new DataSetNotFoundException($dataSet . ' data set not available for this location');
        }

        return $response->get($dataSet);
    }

    /**
     * Build the query parameters
     *
     * @return array
     */
    protected function buildParams(): array
    {
        $this->params = [];
        if ($this->dataSets) {
            $this->params['dataSets'] = implode(',', $this->dataSets);
        }
        if ($this->timezone) {
            $this->params['timezone'] = $this->timezone;
        }
        if ($this->currentAsOf) {
            $this->params['currentAsOf'] = $this->currentAsOf->toIso8601ZuluString();
        }
        if ($this->dailyStart) {
            $this->params['dailyStart'] = $this->dailyStart->toIso8601ZuluString();
        }
        if ($this->dailyEnd) {
            $this->params['dailyEnd'] = $this->dailyEnd->toIso8601ZuluString();
        }
        if ($this->hourlyStart) {
            $this->params['hourlyStart'] = $this->hourlyStart->toIso8601ZuluString();
        }
        if ($this->hourlyEnd) {
            $this->params['hourlyEnd'] = $this->hourlyEnd->toIso8601ZuluString();
        }

        return $this->params;
    }
}