## Laravel WeatherKit

This provides a Laravel style wrapper for Apple's WeatherKit api, which replaced the DarkSky API.

For more information see https://developer.apple.com/weatherkit/get-started/

Please note, Apple requires attribution to use this API in your code https://developer.apple.com/weatherkit/get-started/#attribution-requirements and up to 500,000 calls/month are included with your apple developer account membership.

### Install

Require this package with composer using the following command:

``` bash
$ composer require rich2k/laravel-weatherkit
```

After updating composer, add the service provider to the `providers` array in `config/app.php`

```php
Rich2k\LaravelWeatherKit\LaravelWeatherKitServiceProvider::class,
```

To register a facade accessor, add the following to `config/app.php` `aliases` array

```php
'WeatherKit' => \Rich2k\LaravelWeatherKit\Facades\WeatherKit::class,
```

### Auth Keys

You'll need to first generate a JWT token to access WeatherKit APIs.

 * Setup an App Identifier on the [Identifiers](https://developer.apple.com/account/resources/identifiers/list) page of your Apple paid developer account. 
 * Create a new `App ID` of type App, give it a `Bundle ID` in reverse-domain name style, so com.myapp.weather or similar, and then make sure you select WeatherKit from the App Services tab. This App Identifier can take about 30 minutes to propagate through Apple's systems. 
 * Go to the [Keys](https://developer.apple.com/account/resources/authkeys/list) page in your developer account and add a new key with WeatherKit selected. Remember to download the key file you get at the end!

Now we need to generate your JWT token and public/private keys

First create your private key in a PEM format using `openssl`

`openssl pkcs8 -nocrypt -in AuthKey_ABC1235XS.p8 -out AuthKey_ABC1235XS.pem`

*Note:* the option `-nocrypt` is required!

A public key is also required for signing JWT tokens

`openssl ec -in AuthKey_ABC1235XS.pem -pubout > AuthKey_ABC1235XS.pub`

You should now have two files, a public and a private key. These will be used to sign your JWT token.

Use a JWT token generator such as https://jwt.io/

For the header you want

```json
{
  "alg": "ES256",
  "kid": "<Your 10 digit WeatherKit Key ID>",
  "id": "<Apple Team ID>.<App Identifier>"
}
```

The payload

```json
{
  "iss": "<Apple Team ID>",
  "iat": <Unix Timestamp Now>,
  "exp": <Unix Timestamp to Expire Key>,
  "sub": "<App Identifier>"
}
```

E.g. 

```json
{
  "alg": "ES256",
  "kid": "ABC1234567",
  "id": "DEV1234567.com.myapp.weather"
},
{
"iss": "DEV1234567",
"iat": 1670851291,
"exp": 1702385664,
"sub": "com.myapp.weather"
}
```

Copy and paste your private and public key into the signature verification, and the output is what you need to add to your configuration.

### Configuration

Add the following line to the .env file:

```sh
WEATHERKIT_JWT_TOKEN=<your_weatherkit_jwt_token>
```


### Usage
For full details of response formats, visit: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude

There are two endpoints available at present, availability and weather.

Availability allows you to retrieve which data sets are available for a given location. If you call the availability function before the weather one, we will automatically set the requested datasets to this available.

#### Required
##### location(lat, lon)
Pass in latitude and longitude coordinates for a basic response
``` php
WeatherKit::location(lat, lon)->get();
```

#### Optional Parameters

##### language(lang)
Pass in a language code to return text based responses in the requested language. By default this is `en_US`

``` php
WeatherKit::lang('en_GB')->location(lat, lon)->get();
```

##### dataSets([])
Specify which data sets to use to reduce data transfer.

By default we will try to call `'currentWeather', 'forecastDaily', 'forecastHourly', 'forecastNextHour'`, however you can set these manually with `dataSets()` function. You can also dynamically set this by calling `availability()` before `get()`

```php
WeatherKit::location(lat, lon)->dataSets(['currentWeather', 'forecastDaily'])->get();

// OR

WeatherKit::location(lat, lon)->availability();
WeatherKit::location(lat, lon)->get();
```

##### currentAsOf(t)
Pass in a Carbon object of time to obtain current conditions. Defaults to now.

``` php
WeatherKit::location(lat, lon)->currentAsOf(now())->get();
```

##### dailyStart(t)/dailyEnd(t)
`dailyStart()`: The time to start the daily forecast. If this parameter is absent, daily forecasts start on the current day.
`dailyEnd()`: The time to end the daily forecast. If this parameter is absent, daily forecasts run for 10 days.

``` php
WeatherKit::location(lat, lon)->dailyStart(now()->subDays(7))->dailyEnd(now())->get();
```

##### hourlyStart(t)/hourlyEnd(t)
`hourlyStart()`: The time to start the hourly forecast. If this parameter is absent, hourly forecasts start on the current hour.
`hourlyEnd()`: The time to end the hourly forecast. If this parameter is absent, hourly forecasts run 24 hours or the length of the daily forecast, whichever is longer.

``` php
WeatherKit::location(lat, lon)->hourlyStart(now()->subHours(24))->hourlyEnd(now())->get();
```

##### timezone(timezone)
The name of the timezone to use for rolling up weather forecasts into daily forecasts. Defaults to unset, as this is not required unless calling daily forecasts

``` php
WeatherKit::location(lat, lon)->timezone('Americas/Los_Angeles')->get();
```

#### Helpers
The following are shorthand helpers to add readability equivalent to using `dataSets` set to a single object.
```php
->currently()
->hourly()
->daily()
```
For example, these two statements are the same
```php
WeatherKit::location(lat, lon)->hourly()
WeatherKit::location(lat, lon)->dataSets(['hourly'])->get()->hourly
```

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
