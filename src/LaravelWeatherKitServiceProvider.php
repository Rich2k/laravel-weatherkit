<?php

namespace Rich2k\LaravelWeatherKit;

use Illuminate\Support\ServiceProvider;

class LaravelWeatherKitServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $source = dirname(__DIR__).'/config/weatherkit.php';

        $this->publishes([$source => config_path('weatherkit.php')]);

        $this->mergeConfigFrom($source, 'weatherkit');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('weatherkit',function($app)
        {
            return new WeatherKit();
        });
    }
}
