<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use App\Domain\Weather\Contracts\GeocodingProvider;
use App\Domain\Weather\DTO\CityLocationDTO;

final class OpenMeteoGeocodingProvider implements GeocodingProvider
{
    public function __construct(
        private readonly OpenMeteoGeocodingClient $client,
        private readonly OpenMeteoMapper $mapper,
    ) {}

    public function resolveCity(string $cityName): ?CityLocationDTO
    {
        $raw = $this->client->search($cityName, count: 1, language: 'es', format: 'json');

        return $this->mapper->toCityLocationDTO($raw, fallbackName: $cityName);
    }
}
