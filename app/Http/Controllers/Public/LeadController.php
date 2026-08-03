<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLeadRequest;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

/**
 * Lead capture — the only write endpoint of the public API.
 */
class LeadController extends Controller
{
    /**
     * Store a lead submitted from a public site form.
     *
     * The stored lead is not returned: the public site has nothing to read back,
     * and the admin panel is the only consumer of this data.
     */
    public function store(StoreLeadRequest $request, Site $site): JsonResponse
    {
        $site->leads()->create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Gracias por contactarnos. Un asesor se comunicará contigo.'],
        ], 201);
    }
}
