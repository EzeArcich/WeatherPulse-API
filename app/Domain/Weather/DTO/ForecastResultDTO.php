<?php

namespace App\Domain\Weather\DTO;

final class ForecastResultDTO
{
    /**
     * @param  array<int,HourlyWeatherReadingDTO>  $hourly
     */
    public function __construct(
        public readonly WeatherReadingDTO $current,
        public readonly array $hourly = [],
    ) {}
}
