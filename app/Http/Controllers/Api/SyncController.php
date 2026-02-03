<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAllLocationsJob;

final class SyncController extends Controller
{
    public function sync()
    {
        SyncAllLocationsJob::dispatch();

        return response()->json([
            'message' => 'Sync encolado',
        ], 202);
    }
}

