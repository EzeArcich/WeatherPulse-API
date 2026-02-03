<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class SyncAllLocationsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        Location::query()->select('id')->chunkById(200, function ($locations) {
            foreach ($locations as $location) {
                SyncLocationWeatherJob::dispatch($location->id);
            }
        });
    }
}
