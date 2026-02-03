<?php

namespace App\Application\Services;

use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Domain\Weather\Contracts\ForecastProvider;
use App\Domain\Weather\DTO\CityLocationDTO;
use App\Domain\Weather\DTO\WeatherReportDTO;
use Illuminate\Support\Facades\Cache;

final class WeatherSearchService
{
    public function __construct(
        private readonly GeocodingProvider $geocoding,
        private readonly ForecastProvider $forecast,
    ) {}

    public function resolveCity(string $cityName): CityLocationDTO
    {
        $city = $this->geocoding->resolveCity($cityName);

        if (!$city) {
            abort(404, 'City not found');
        }

        return $city;
    }

    public function searchByCityName(string $cityName): WeatherReportDTO
    {
        $location = $this->resolveCity($cityName);

        $cacheKey = 'weather:search:' . md5(mb_strtolower($location->name.'|'.$location->latitude.'|'.$location->longitude));
        $ttlSeconds = 15 * 60;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($location) {
            $current = $this->forecast->current($location->latitude, $location->longitude, $location->timezone);

            return new WeatherReportDTO($location, $current, stale: false);
        });
    }
}
