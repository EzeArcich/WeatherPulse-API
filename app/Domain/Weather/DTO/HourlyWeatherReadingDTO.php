<?php

namespace App\Domain\Weather\DTO;

final class HourlyWeatherReadingDTO
{
    public function __construct(
        public readonly \DateTimeImmutable $time,
        public readonly ?float $temperatureC,
        public readonly ?float $windKph,
        public readonly ?float $precipMm,
        public readonly ?int $precipitationProbability,
        public readonly ?int $humidity,
    ) {}

    public function toArray(): array
    {
        return [
            'time' => $this->time->format(DATE_ATOM),
            'temperature_c' => $this->temperatureC,
            'wind_kph' => $this->windKph,
            'precip_mm' => $this->precipMm,
            'precipitation_probability' => $this->precipitationProbability,
            'humidity' => $this->humidity,
        ];
    }
}
