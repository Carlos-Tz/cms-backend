<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StationRequest;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stations are data, not blueprint structure: full CRUD, like blog posts.
 * Inactive stations are listed here but never in the public API.
 */
class StationController extends Controller
{
    /**
     * List stations, optionally filtered by site slug. Inactive included.
     */
    public function index(Request $request): JsonResponse
    {
        $stations = Station::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $stations->items(),
            'meta' => [
                'page' => $stations->currentPage(),
                'per_page' => $stations->perPage(),
                'total' => $stations->total(),
                'last_page' => $stations->lastPage(),
            ],
        ]);
    }

    public function store(StationRequest $request): JsonResponse
    {
        $station = Station::create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $station,
            'meta' => ['message' => 'Estación creada correctamente'],
        ], 201);
    }

    public function show(Station $station): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $station,
            'meta' => [],
        ]);
    }

    public function update(StationRequest $request, Station $station): JsonResponse
    {
        $station->update($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $station->fresh(),
            'meta' => ['message' => 'Estación actualizada correctamente'],
        ]);
    }

    public function destroy(Station $station): JsonResponse
    {
        $station->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Estación eliminada correctamente'],
        ]);
    }
}
