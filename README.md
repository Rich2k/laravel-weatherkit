# Laravel WeatherKit

This provides a Laravel style wrapper for Apple's WeatherKit api, which replaced the DarkSky API.

For more information see https://developer.apple.com/weatherkit/get-started/

Please note, Apple requires attribution to use this API in your code https://developer.apple.com/weatherkit/get-started/#attribution-requirements and up to 500,000 calls/month are included with your apple developer account membership.

## Install

Require this package with composer using the following command:

``` bash
$ composer require rich2k/laravel-weatherkit
```

### Providers

This library works out-of-the-box with Laravel's Service Providers and will be loaded automatically in Laravel `>= 5.5`.

You can, of course, add it manually to your `providers` array in `config/app.php` if you'd prefer

```php
'providers' => [
    Rich2k\LaravelWeatherKit\Providers\LaravelServiceProvider::class,
]
```

### Facade

To register a facade accessor, add the following to `config/app.php` `aliases` array

```php
'aliases' => [
    'WeatherKit' => Rich2k\LaravelWeatherKit\Facades\WeatherKit::class,
]
```

### Configuration 

See [Authentication](#Authentication) section on how to use these environment variables.

| Variable name                | Default                         | Description                                   |
|------------------------------|---------------------------------|-----------------------------------------------|
| `WEATHERKIT_AUTH_TYPE`       | `jwt`                           | `jwt` or `p8` token generation                |
| ---------------------------- |---------------------------------| ---------------------------------             |
| `WEATHERKIT_JWT_TOKEN`       |                                 | A pre-generated JWT token.                    |
| ---------------------------- |---------------------------------| ---------------------------------             |
| `WEATHERKIT_KEY`             |                                 | Path to the `.p8` key file or key as a string |
| `WEATHERKIT_KEY_ID`          |                                 | Key ID for you `.p8` file                     |
| `WEATHERKIT_TEAM_ID`         |                                 | Your Apple Team ID                            |
| `WEATHERKIT_BUNDLE_ID`       |                                 | Bundle ID of your App                         |
| `WEATHERKIT_TOKEN_TTL`       | `3600`                          | Expiry time of token in seconds               |
| ---------------------------- |---------------------------------| ---------------------------------             |
| `WEATHERKIT_LANGUAGE_CODE`   | `config('app.locale', 'en')`    | Language code                                 |
| `WEATHERKIT_TIMEZONE`        | `config('app.timezone', 'UTC')` | Timezone for timestamps                       |

If you wish to change the default configuration, you can publish the configuration file to your project.

```bash
$ php artisan vendor:publish --provider=\Rich2k\LaravelWeatherKit\Providers\LaravelServiceProvider
```

## Authentication

There are two ways to authenticate with WeatherKit using this library. You'll need to generate the key file first for whichever method you choose.

### Generate Key File

If you wish to generate and manage your own JWT Token yourself then you'll need to first generate a JWT token to access WeatherKit APIs.

You'll need to be enrolled in the paid Apple Developer Program, and register a new App ID and create a key.

#### Create new App ID

Create an App Identifier on the [Identifiers](https://developer.apple.com/account/resources/identifiers/list) section of your account. Enter a short description and give your app a unique bundle ID (e.g. com.myapp.weather).

Make sure you check the WeatherKit option under *BOTH* the Capabilities and App Services tabs. Click on Continue.

#### Create a Key

Go to the [Keys](https://developer.apple.com/account/resources/authkeys/list) page in your developer account.  

Give the key a name, e.g. WeatherKit, and make sure to enable WeatherKit. Then click the Continue button. Then you'll be taken to a page with a Register button.

Remember to download the key file you get at the end!

#### Required Information

Whichever authentication method you decide to use, we are going to need some additional information first.

* You Apple Team ID
* The App Bundle ID that you created earlier (reverse DNS).
* The Key ID of the key, that you created in the Create new key section, you can get this at any point after generation.
* The physical key file ending in `.p8` you downloaded.

### Manual JWT Token Generation

Once you've generated and downloaded your `.p8` key file above, we now need to generate your JWT token and public/private keys

Create your private key in a PEM format using `openssl`

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

Copy and paste your private and public key into the signature verification, and the output is what you need to add to your configuration `WEATHERKIT_JWT_TOKEN`.

#### Configuration

Add the following lines to the .env file:

```sh
WEATHERKIT_AUTH_TYPE=jwt
WEATHERKIT_JWT_TOKEN=<your_weatherkit_jwt_token>
```

### Dynamic Token Generation

Starting with library version `>=1.2` you can dynamically generate your JWT token direct

#### Configuration

Add the following lines to the .env file:

```sh
WEATHERKIT_AUTH_TYPE=p8
WEATHERKIT_KEY=<Path To Key File/Key String>
WEATHERKIT_KEY_ID=<Key Id>
WEATHERKIT_TEAM_ID=<Team Id>
WEATHERKIT_BUNDLE_ID=<Bundle ID>
```


## Usage
For full details of response formats, visit: https://developer.apple.com/documentation/weatherkitrestapi/get_api_v1_weather_language_latitude_longitude

There are two endpoints available at present, availability and weather.

Availability allows you to retrieve which data sets are available for a given location. If you call the availability function before the weather one, we will automatically set the requested datasets to this available.

`availability()` and `weather()` functions will return their results as a Laravel `Collection`

### Required
#### location(lat, lon)
Pass in latitude and longitude coordinates for a basic response
``` php
WeatherKit::location(lat, lon)->weather();
```

### Optional Parameters

#### language(lang)
Pass in a language code to return text based responses in the requested language. By default this is `en_US`

``` php
WeatherKit::lang('en_GB')->location(lat, lon)->weather();
```

#### dataSets([])
Specify which data sets to use to reduce data transfer.

By default we will try to call `'currentWeather', 'forecastDaily', 'forecastHourly', 'forecastNextHour'`, however you can set these manually with `dataSets()` function. You can also dynamically set this by calling `availability()` before `weather()` when not using through a facade.

```php
WeatherKit::location(lat, lon)->dataSets(['currentWeather', 'forecastDaily'])->weather();

// OR

$weather = new \Rich2k\LaravelWeatherKit\WeatherKit();
$weather->location(lat, lon)->availability();
$weather->location(lat, lon)->weather();
```

#### currentAsOf(t)
Pass in a Carbon object of time to obtain current conditions. Defaults to now.

``` php
WeatherKit::location(lat, lon)->currentAsOf(now())->weather();
```

#### dailyStart(t)/dailyEnd(t)
`dailyStart()`: The time to start the daily forecast. If this parameter is absent, daily forecasts start on the current day.
`dailyEnd()`: The time to end the daily forecast. If this parameter is absent, daily forecasts run for 10 days.

``` php
WeatherKit::location(lat, lon)->dailyStart(now()->subDays(7))->dailyEnd(now())->weather();
```

#### hourlyStart(t)/hourlyEnd(t)
`hourlyStart()`: The time to start the hourly forecast. If this parameter is absent, hourly forecasts start on the current hour.
`hourlyEnd()`: The time to end the hourly forecast. If this parameter is absent, hourly forecasts run 24 hours or the length of the daily forecast, whichever is longer.

``` php
WeatherKit::location(lat, lon)->hourlyStart(now()->subHours(24))->hourlyEnd(now())->weather();
```

#### timezone(timezone)
The name of the timezone to use for rolling up weather forecasts into daily forecasts. Defaults to unset, as this is not required unless calling daily forecasts

``` php
WeatherKit::location(lat, lon)->timezone('Americas/Los_Angeles')->weather();
```

### Helpers
The following are shorthand helpers to add readability equivalent to using `dataSets` set to a single object.
```php
->currently()
->hourly()
->daily()
->nextHour()
```
For example, these two statements are the same
```php
WeatherKit::location(lat, lon)->hourly()
WeatherKit::location(lat, lon)->dataSets(['forecastHourly'])->weather()->get('forecastHourly')
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
