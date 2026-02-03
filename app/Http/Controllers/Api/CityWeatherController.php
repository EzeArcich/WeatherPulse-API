<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\WeatherSnapshot;
use Illuminate\Http\Request;

final class CityWeatherController extends Controller
{
    public function latest(Location $location)
    {
        $snap = WeatherSnapshot::query()
            ->where('location_id', $location->id)
            ->latest('observed_at')
            ->first();

        return response()->json([
            'city' => $location,
            'data' => $snap,
        ]);
    }

    public function snapshots(Request $request, Location $location)
    {
        $from = $request->query('from'); // YYYY-MM-DD (opcional)
        $to   = $request->query('to');   // YYYY-MM-DD (opcional)

        $q = WeatherSnapshot::query()
            ->where('location_id', $location->id)
            ->orderBy('observed_at');

        if ($from) $q->whereDate('observed_at', '>=', $from);
        if ($to)   $q->whereDate('observed_at', '<=', $to);

        return response()->json([
            'city' => $location,
            'data' => $q->get(),
        ]);
    }
}

