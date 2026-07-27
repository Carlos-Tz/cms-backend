<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Return a published page with its sections, in blueprint order.
     */
    public function show(Site $site, string $pageSlug): JsonResponse
    {
        $page = $site->pages()
            ->where('slug', $pageSlug)
            ->where('is_published', true)
            ->with('sections')
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'slug' => $page->slug,
                'path' => $page->path,
                'title' => $page->title,
                'sections' => $page->sections->map($this->presentSection(...))->all(),
            ],
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * Return the site-wide sections (contact info, social icons, whatsapp button),
     * keyed by type.
     *
     * They live in this controller rather than their own because they are the same
     * resource as a page's sections, only without a page.
     */
    public function globals(Site $site): JsonResponse
    {
        $sections = $site->globalSections()->get();

        return response()->json([
            'status' => 'success',
            'data' => $sections->mapWithKeys(fn (Section $section) => [$section->type => $section->content]),
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * @return array{type: string, content: array<string, mixed>}
     */
    private function presentSection(Section $section): array
    {
        return [
            'type' => $section->type,
            'content' => $section->content,
        ];
    }
}
