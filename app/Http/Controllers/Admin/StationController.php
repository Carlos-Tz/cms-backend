<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder controller — real implementation comes in a later phase.
 */
class StationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'stations']]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'stations']], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'stations', 'id' => $id]]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'stations', 'id' => $id]]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'stations', 'id' => $id]]);
    }
}
