<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Only `store` is implemented — the rest stays a placeholder until there is a
 * media library to list.
 */
class MediaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'media']]);
    }

    /**
     * Upload an image and return its public URL.
     *
     * The URL is what gets stored inside a section's JSON content — there is no
     * media table yet, so uploads are not listable or deletable through the API.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ], [], ['file' => 'archivo']);

        $path = $request->file('file')->store('media', 'public');

        return response()->json([
            'status' => 'success',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
            'meta' => ['message' => 'Archivo subido correctamente'],
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'media', 'id' => $id]]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'media', 'id' => $id]]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['resource' => 'media', 'id' => $id]]);
    }
}
