<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder controller — real implementation comes in a later phase.
 * This is the only write endpoint on the public API (lead capture).
 */
class LeadController extends Controller
{
    /**
     * Store a lead submitted from a public site form.
     */
    public function store(Request $request, string $site): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['resource' => 'leads', 'site' => $site],
        ], 201);
    }
}
