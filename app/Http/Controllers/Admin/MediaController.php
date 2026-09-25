<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Image library for the admin panel, backed by the `media` folder of the public
 * disk. There is no `media` table: the only metadata needed today is the name
 * and the URL, and both come from the disk. A table is worth it once alt text
 * or upload traceability is asked for.
 *
 * No `show` or `update`: a file has nothing to detail and nothing to patch.
 */
class MediaController extends Controller
{
    /**
     * List the uploaded images, newest first.
     */
    public function index(): JsonResponse
    {
        $disk = Storage::disk('public');

        $files = collect($disk->files('media'))
            ->sortByDesc(fn (string $path) => $disk->lastModified($path))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'url' => $disk->url($path),
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $files,
            'meta' => ['total' => $files->count()],
        ]);
    }

    /**
     * Upload an image and return its public URL.
     *
     * The path is what gets stored inside a section's JSON content.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:20480'],
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

    /**
     * Delete an uploaded image by file name.
     *
     * The router is what actually stops a traversal: `{medium}` matches a single
     * path segment, so `../.env` 404s before this action runs. `basename()` is the
     * second line of defence, for the day that route constraint changes.
     */
    public function destroy(string $medium): JsonResponse
    {
        $path = 'media/'.basename($medium);

        abort_unless(Storage::disk('public')->exists($path), 404);

        Storage::disk('public')->delete($path);

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Archivo eliminado correctamente'],
        ]);
    }
}
