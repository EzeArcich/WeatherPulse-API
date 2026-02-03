<?php

namespace App\Jobs;

use App\Application\Services\WeatherSyncService;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncLocationWeatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $cityId) {}

    public function handle(WeatherSyncService $service): void
    {
        $city = Location::find($this->cityId);
        if (!$city) return;

        $service->syncCity($city);
    }
}
