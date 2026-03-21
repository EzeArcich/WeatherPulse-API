<?php

namespace App\Domain\Weather\DTO;

final class WeatherReportDTO
{
    /**
     * @param  array<int,HourlyWeatherReadingDTO>  $hourly
     */
    public function __construct(
        public readonly CityLocationDTO $city,
        public readonly WeatherReadingDTO $current,
        public readonly array $hourly = [],
        public readonly bool $stale = false,
    ) {}

    public function toArray(): array
    {
        return [
            'city' => [
                'name' => $this->city->name,
                'country' => $this->city->country,
                'lat' => $this->city->latitude,
                'lon' => $this->city->longitude,
                'timezone' => $this->city->timezone,
            ],
            'current' => [
                'observed_at' => $this->current->observedAt->format(DATE_ATOM),
                'temperature_c' => $this->current->temperatureC,
                'wind_kph' => $this->current->windKph,
                'precip_mm' => $this->current->precipMm,
                'precipitation_probability' => $this->current->precipitationProbability,
                'humidity' => $this->current->humidity,
            ],
            'hourly' => array_map(
                static fn (HourlyWeatherReadingDTO $reading) => $reading->toArray(),
                $this->hourly
            ),
            'stale' => $this->stale,
        ];
    }
}
