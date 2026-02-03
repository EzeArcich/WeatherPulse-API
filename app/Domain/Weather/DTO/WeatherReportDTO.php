<?php

namespace App\Domain\Weather\DTO;

final class WeatherReportDTO
{
    public function __construct(
        public readonly CityLocationDTO $city,
        public readonly WeatherReadingDTO $current,
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
                'humidity' => $this->current->humidity,
            ],
            'stale' => $this->stale,
        ];
    }
}
