<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Domain\Weather\Contracts\ForecastProvider;
use App\Infrastructure\Weather\OpenMeteo\OpenMeteoGeocodingProvider;
use App\Infrastructure\Weather\OpenMeteo\OpenMeteoForecastProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeocodingProvider::class, OpenMeteoGeocodingProvider::class);
        $this->app->bind(ForecastProvider::class, OpenMeteoForecastProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
