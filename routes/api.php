<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeatherSearchController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityWeatherController;
use App\Http\Controllers\Api\SyncController;

Route::get('/weather/search', [WeatherSearchController::class, 'search']);

Route::apiResource('cities', CityController::class)
    ->parameters(['cities' => 'location'])
    ->only(['index','store','destroy']);

Route::get('/cities/{location}/latest', [CityWeatherController::class, 'latest']);
Route::get('/cities/{location}/snapshots', [CityWeatherController::class, 'snapshots']);

Route::post('/sync', [SyncController::class, 'sync']);
