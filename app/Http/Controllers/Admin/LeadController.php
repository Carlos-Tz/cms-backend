<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only inbox for the admin panel: leads are only born on the public site,
 * so there is no store or update here.
 */
class LeadController extends Controller
{
    /**
     * List captured leads, newest first, optionally filtered by site slug.
     */
    public function index(Request $request): JsonResponse
    {
        $leads = Lead::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $leads->items(),
            'meta' => [
                'page' => $leads->currentPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
                'last_page' => $leads->lastPage(),
            ],
        ]);
    }

    /**
     * The site is loaded so the panel can show where the lead came from.
     */
    public function show(Lead $lead): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $lead->load('site'),
            'meta' => [],
        ]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Lead eliminado correctamente'],
        ]);
    }
}
