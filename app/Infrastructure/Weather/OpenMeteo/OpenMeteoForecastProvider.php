<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use App\Domain\Weather\Contracts\ForecastProvider;
use App\Domain\Weather\DTO\WeatherReadingDTO;

final class OpenMeteoForecastProvider implements ForecastProvider
{
    public function __construct(
        private readonly OpenMeteoForecastClient $client,
        private readonly OpenMeteoMapper $mapper,
    ) {}

    public function current(float $lat, float $lon, ?string $timezone = null): WeatherReadingDTO
    {
        $raw = $this->client->forecast($lat, $lon, $timezone ?? 'auto');

        $dto = $this->mapper->toWeatherReadingDTO($raw);

        if (!$dto) {
            abort(502, 'Failed to read weather data from provider');
        }

        return $dto;
    }
}
