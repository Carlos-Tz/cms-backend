<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder controller — real implementation comes in a later phase.
 */
class StationController extends Controller
{
    /**
     * List map stations/locations for the given site.
     */
    public function index(string $site): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'stations', 'site' => $site]]);
    }
}
