<?php

namespace App\Domain\Weather\DTO;

final class CityLocationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $country,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $timezone,
    ) {}
}
