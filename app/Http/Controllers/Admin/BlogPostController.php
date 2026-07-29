<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostRequest;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Blog posts are data, not blueprint structure: full CRUD, unlike sections.
 * Drafts (`published_at` = null) are listed here but never in the public API.
 */
class BlogPostController extends Controller
{
    /**
     * List posts, optionally filtered by site slug. Drafts included.
     */
    public function index(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            // Newest published first, drafts last (NULL sorts last when descending).
            ->orderByDesc('published_at')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $posts->items(),
            'meta' => [
                'page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function store(BlogPostRequest $request): JsonResponse
    {
        $post = BlogPost::create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $post,
            'meta' => ['message' => 'Artículo creado correctamente'],
        ], 201);
    }

    public function show(BlogPost $blogPost): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $blogPost,
            'meta' => [],
        ]);
    }

    public function update(BlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $blogPost->update($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $blogPost->fresh(),
            'meta' => ['message' => 'Artículo actualizado correctamente'],
        ]);
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $blogPost->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Artículo eliminado correctamente'],
        ]);
    }
}
