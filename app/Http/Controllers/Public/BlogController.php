<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder controller — real implementation comes in a later phase.
 * Only applies to sites that have a blog (gnu, gnu-vehicular).
 */
class BlogController extends Controller
{
    /**
     * List published blog posts for the given site.
     */
    public function index(string $site): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['resource' => 'blog', 'site' => $site]]);
    }

    /**
     * Return a single published blog post by slug.
     */
    public function show(string $site, string $postSlug): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['resource' => 'blog', 'site' => $site, 'slug' => $postSlug],
        ]);
    }
}
