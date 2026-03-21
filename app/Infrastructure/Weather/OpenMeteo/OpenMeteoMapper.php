<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use App\Domain\Weather\DTO\CityLocationDTO;
use App\Domain\Weather\DTO\ForecastResultDTO;
use App\Domain\Weather\DTO\HourlyWeatherReadingDTO;
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

        $precipitationProbability = $this->resolvePrecipitationProbability($raw, $time);

        return new WeatherReadingDTO(
            observedAt: $observedAt,
            temperatureC: (float)$temp,
            windKph: (float)$wind,
            precipMm: $precip,
            precipitationProbability: $precipitationProbability,
            humidity: $humidity,
            raw: $raw
        );
    }

    public function toForecastResultDTO(array $raw): ?ForecastResultDTO
    {
        $current = $this->toWeatherReadingDTO($raw);
        if (!$current) {
            return null;
        }

        return new ForecastResultDTO(
            current: $current,
            hourly: $this->mapHourlyReadings($raw),
        );
    }

    private function resolvePrecipitationProbability(array $raw, string $currentTime): ?int
    {
        $times = $this->extractHourlyTimes($raw);
        $index = array_search($currentTime, $times, true);

        if ($index === false) {
            $currentHour = $this->normalizeHour($currentTime);

            foreach ($times as $candidateIndex => $time) {
                if (!is_string($time)) {
                    continue;
                }

                if ($this->normalizeHour($time) === $currentHour) {
                    $index = $candidateIndex;
                    break;
                }
            }
        }

        if ($index === false) {
            return null;
        }

        $value = $this->extractHourlyProbabilities($raw)[$index] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<int,HourlyWeatherReadingDTO>
     */
    private function mapHourlyReadings(array $raw): array
    {
        $times = $this->extractHourlyTimes($raw);
        $temperatures = $this->extractHourlyTemperatures($raw);
        $winds = $this->extractHourlyWinds($raw);
        $precipitations = $this->extractHourlyPrecipitations($raw);
        $probabilities = $this->extractHourlyProbabilities($raw);
        $humidities = $this->extractHourlyHumidities($raw);

        $items = [];

        foreach ($times as $index => $time) {
            if (!is_string($time) || $time === '') {
                continue;
            }

            try {
                $at = new \DateTimeImmutable($time);
            } catch (\Throwable) {
                continue;
            }

            $temperature = $temperatures[$index] ?? null;
            $wind = $winds[$index] ?? null;
            $precipitation = $precipitations[$index] ?? null;
            $probability = $probabilities[$index] ?? null;
            $humidity = $humidities[$index] ?? null;

            $items[] = new HourlyWeatherReadingDTO(
                time: $at,
                temperatureC: is_numeric($temperature) ? (float) $temperature : null,
                windKph: is_numeric($wind) ? (float) $wind : null,
                precipMm: is_numeric($precipitation) ? (float) $precipitation : null,
                precipitationProbability: is_numeric($probability) ? (int) $probability : null,
                humidity: is_numeric($humidity) ? (int) $humidity : null,
            );
        }

        return $items;
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyTimes(array $raw): array
    {
        $hourly = $raw['hourly'] ?? null;

        if (!is_array($hourly) || !is_array($hourly['time'] ?? null)) {
            return [];
        }

        return $hourly['time'];
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyProbabilities(array $raw): array
    {
        $hourly = $raw['hourly'] ?? null;

        if (!is_array($hourly) || !is_array($hourly['precipitation_probability'] ?? null)) {
            return [];
        }

        return $hourly['precipitation_probability'];
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyTemperatures(array $raw): array
    {
        return $this->extractHourlyMetric($raw, 'temperature_2m');
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyWinds(array $raw): array
    {
        return $this->extractHourlyMetric($raw, 'wind_speed_10m');
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyPrecipitations(array $raw): array
    {
        return $this->extractHourlyMetric($raw, 'precipitation');
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyHumidities(array $raw): array
    {
        return $this->extractHourlyMetric($raw, 'relative_humidity_2m');
    }

    /**
     * @return array<int,mixed>
     */
    private function extractHourlyMetric(array $raw, string $key): array
    {
        $hourly = $raw['hourly'] ?? null;

        if (!is_array($hourly) || !is_array($hourly[$key] ?? null)) {
            return [];
        }

        return $hourly[$key];
    }

    private function normalizeHour(string $time): ?string
    {
        try {
            return (new \DateTimeImmutable($time))->format('Y-m-d\TH:00');
        } catch (\Throwable) {
            return null;
        }
    }
}
