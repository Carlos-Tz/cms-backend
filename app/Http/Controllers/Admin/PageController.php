<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder controller — real implementation comes in a later phase.
 */
class PageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'pages']]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'pages']], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'pages', 'id' => $id]]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'pages', 'id' => $id]]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'pages', 'id' => $id]]);
    }
}
