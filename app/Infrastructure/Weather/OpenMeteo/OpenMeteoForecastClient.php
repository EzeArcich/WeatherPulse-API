<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use Illuminate\Support\Facades\Http;

final class OpenMeteoForecastClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.open-meteo.com/v1'
    ) {}

    /** @return array<string,mixed> */
    public function forecast(float $lat, float $lon, ?string $timezone = 'auto'): array
    {
        // Pedimos current + algunos campos útiles (podés ajustar)
        $resp = Http::timeout(7)
            ->retry(2, 250)
            ->get($this->baseUrl . '/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m',
                'timezone' => $timezone ?? 'auto',
            ]);

        if ($resp->failed()) {
            return [];
        }

        return $resp->json() ?? [];
    }
}
