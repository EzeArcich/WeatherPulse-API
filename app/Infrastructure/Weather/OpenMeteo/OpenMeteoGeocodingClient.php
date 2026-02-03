<?php

namespace App\Infrastructure\Weather\OpenMeteo;

use Illuminate\Support\Facades\Http;

final class OpenMeteoGeocodingClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://geocoding-api.open-meteo.com/v1'
    ) {}

    /** @return array<string,mixed> */
    public function search(string $name, int $count = 1, string $language = 'en', string $format = 'json'): array
    {
        $resp = Http::timeout(7)
            ->retry(2, 250)
            ->get($this->baseUrl . '/search', [
                'name' => $name,
                'count' => $count,
                'language' => $language,
                'format' => $format,
            ]);

        if ($resp->failed()) {
            // En app real: log + excepción propia
            return [];
        }

        return $resp->json() ?? [];
    }
}
