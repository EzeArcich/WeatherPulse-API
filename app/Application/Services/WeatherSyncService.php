<?php

namespace App\Application\Services;

use App\Domain\Weather\Contracts\ForecastProvider;
use App\Models\Location;
use App\Models\WeatherSnapshot;

final class WeatherSyncService
{
    public function __construct(
        private readonly ForecastProvider $forecast,
    ) {}

    public function syncCity(Location $location): void
    {
        $dto = $this->forecast->current($location->lat, $location->lon, $location->timezone);

        WeatherSnapshot::query()->updateOrCreate(
            [
                'location_id' => $location->id,
                'observed_at' => $dto->observedAt->format('Y-m-d H:i:s'),
            ],
            [
                'temperature_c' => $dto->temperatureC,
                'wind_kph' => $dto->windKph,
                'precip_mm' => $dto->precipMm,
                'humidity' => $dto->humidity,
                'raw' => $dto->raw,
            ]
        );
    }
}
