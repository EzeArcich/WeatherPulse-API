<?php

namespace App\Domain\Weather\Contracts;

use App\Domain\Weather\DTO\CityLocationDTO;

interface GeocodingProvider
{
    public function resolveCity(string $cityName): ?CityLocationDTO;
}
