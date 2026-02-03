<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeatherSearchRequest;
use App\Application\Services\WeatherSearchService;

final class WeatherSearchController extends Controller
{
    public function __construct(
        private readonly WeatherSearchService $service
    ) {}

    public function search(WeatherSearchRequest $request)
    {
        $report = $this->service->searchByCityName($request->city());

        return response()->json($report->toArray());
    }
}

