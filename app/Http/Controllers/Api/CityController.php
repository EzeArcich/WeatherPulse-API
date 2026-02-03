<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityStoreRequest;
use App\Models\Location;
use App\Application\Services\WeatherSearchService;

final class CityController extends Controller
{
    public function __construct(
        private readonly WeatherSearchService $searchService
    ) {}

    public function index()
    {
        return response()->json([
            'data' => Location::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CityStoreRequest $request)
    {
        // reutilizamos geocoding del SearchService para obtener lat/lon
        $location = $this->searchService->resolveCity($request->name());

        $city = Location::query()->firstOrCreate(
            [
                'name' => $location->name,
                'lat' => $location->latitude,
                'lon' => $location->longitude,
            ],
            [
                'country' => $location->country,
                'timezone' => $location->timezone,
            ]
        );

        return response()->json(['data' => $city], 201);
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return response()->json([], 204);
    }
}

