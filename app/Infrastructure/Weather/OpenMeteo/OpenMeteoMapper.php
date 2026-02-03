<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use App\Domain\Weather\DTO\CityLocationDTO;
use App\Domain\Weather\DTO\WeatherReadingDTO;

final class OpenMeteoMapper
{
    public function toCityLocationDTO(array $raw, string $fallbackName): ?CityLocationDTO
    {
        $results = $raw['results'] ?? null;
        if (!is_array($results) || count($results) === 0) {
            return null;
        }

        $r = $results[0];
        if (!is_array($r)) return null;

        $name = (string)($r['name'] ?? $fallbackName);
        $country = isset($r['country']) ? (string)$r['country'] : null;
        $lat = isset($r['latitude']) ? (float)$r['latitude'] : null;
        $lon = isset($r['longitude']) ? (float)$r['longitude'] : null;
        $tz  = isset($r['timezone']) ? (string)$r['timezone'] : null;

        if ($lat === null || $lon === null) return null;

        return new CityLocationDTO(
            name: $name,
            country: $country,
            latitude: $lat,
            longitude: $lon,
            timezone: $tz
        );
    }

    public function toWeatherReadingDTO(array $raw): ?WeatherReadingDTO
    {
        $current = $raw['current'] ?? null;
        if (!is_array($current)) {
            return null;
        }

        // Open-Meteo suele dar "time" como ISO / local time según timezone.
        $time = $current['time'] ?? null;
        if (!is_string($time) || $time === '') {
            return null;
        }

        try {
            $observedAt = new \DateTimeImmutable($time);
        } catch (\Throwable) {
            return null;
        }

        $temp = $current['temperature_2m'] ?? null;
        $wind = $current['wind_speed_10m'] ?? null;

        if (!is_numeric($temp) || !is_numeric($wind)) {
            return null;
        }

        $humidity = isset($current['relative_humidity_2m']) && is_numeric($current['relative_humidity_2m'])
            ? (int)$current['relative_humidity_2m']
            : null;

        $precip = isset($current['precipitation']) && is_numeric($current['precipitation'])
            ? (float)$current['precipitation']
            : null;

        return new WeatherReadingDTO(
            observedAt: $observedAt,
            temperatureC: (float)$temp,
            windKph: (float)$wind,
            precipMm: $precip,
            humidity: $humidity,
            raw: $raw
        );
    }
}
