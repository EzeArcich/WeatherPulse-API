<?php

namespace App\Domain\Weather\Contracts;

use App\Domain\Weather\DTO\ForecastResultDTO;
use App\Domain\Weather\DTO\WeatherReadingDTO;

interface ForecastProvider
{
    public function current(float $lat, float $lon, ?string $timezone = null): WeatherReadingDTO;

    public function report(float $lat, float $lon, ?string $timezone = null): ForecastResultDTO;
}
