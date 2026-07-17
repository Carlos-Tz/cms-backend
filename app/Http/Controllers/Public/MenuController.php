<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder controller — real implementation comes in a later phase.
 */
class MenuController extends Controller
{
    /**
     * Return the navigation tree for the given site.
     */
    public function show(string $site): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'menu', 'site' => $site]]);
    }
}
