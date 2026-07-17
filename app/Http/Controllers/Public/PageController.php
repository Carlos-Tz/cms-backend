<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder controller — real implementation comes in a later phase.
 */
class PageController extends Controller
{
    /**
     * Return a published page (with its sections) by slug.
     */
    public function show(string $site, string $pageSlug): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['resource' => 'page', 'site' => $site, 'slug' => $pageSlug],
        ]);
    }
}
