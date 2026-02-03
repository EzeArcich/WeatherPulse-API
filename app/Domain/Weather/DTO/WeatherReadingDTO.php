<?php

namespace App\Domain\Weather\DTO;

final class WeatherReadingDTO
{
    public function __construct(
        public readonly \DateTimeImmutable $observedAt,
        public readonly float $temperatureC,
        public readonly float $windKph,
        public readonly ?float $precipMm,
        public readonly ?int $humidity,
        public readonly array $raw, // payload original
    ) {}
}
