<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class MenuController extends Controller
{
    /**
     * Return the navigation tree for the given site.
     *
     * The menu is derived from the page map, never stored: everything it needs
     * already lives in `pages`. Unpublished pages drop out with their whole
     * branch — a child of a hidden parent has no route to reach it.
     */
    public function show(Site $site): JsonResponse
    {
        // One query; the nesting happens in memory. At 17 pages per site a
        // recursive CTE would cost more to read than it saves.
        $pages = $site->pages()
            ->where('is_published', true)
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $this->tree($pages, null),
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array<int, array<string, mixed>>
     */
    private function tree(Collection $pages, ?int $parentId): array
    {
        return $pages
            ->where('parent_id', $parentId)
            ->map(fn (Page $page) => [
                'slug' => $page->slug,
                'path' => $page->path,
                'title' => $page->title,
                'children' => $this->tree($pages, $page->id),
            ])
            ->values()
            ->all();
    }
}
