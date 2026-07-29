<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

/**
 * Read-only blog feed. Sites without a blog simply have no posts, so no
 * per-site flag is needed here — the listing just comes back empty.
 */
class BlogController extends Controller
{
    /**
     * List published blog posts for the given site, newest first.
     */
    public function index(Site $site): JsonResponse
    {
        $posts = $site->blogPosts()->published()->latest('published_at')->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => collect($posts->items())->map(fn (BlogPost $post) => $this->present($post))->all(),
            'meta' => [
                'site' => $site->slug,
                'page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    /**
     * Return a single published blog post by slug.
     */
    public function show(Site $site, string $postSlug): JsonResponse
    {
        $post = $site->blogPosts()->published()->where('slug', $postSlug)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $this->present($post, withBody: true),
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * The listing omits the body: it would multiply the payload for nothing.
     *
     * @return array<string, mixed>
     */
    private function present(BlogPost $post, bool $withBody = false): array
    {
        $data = [
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image,
            'published_at' => $post->published_at?->toDateString(),
        ];

        return $withBody ? [...$data, 'body' => $post->body] : $data;
    }
}
