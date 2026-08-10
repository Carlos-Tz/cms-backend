<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pages are structure: the admin may only publish and unpublish them.
 * Creating and deleting pages belongs to the blueprint, so this controller
 * exposes index / show / update only.
 */
class PageController extends Controller
{
    /**
     * List pages, optionally filtered by site slug.
     *
     * Flat and ordered rather than nested: `parent_id` is enough for the panel
     * to nest them, and a table view wants the flat shape anyway. Unpublished
     * pages are included — this is how the admin finds them again.
     */
    public function index(Request $request): JsonResponse
    {
        $pages = Page::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->withCount('sections')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pages->map(
                fn (Page $page) => $this->present($page) + ['sections_count' => $page->sections_count],
            )->all(),
            'meta' => ['total' => $pages->count()],
        ]);
    }

    /**
     * A single page with its sections, so the panel loads an edit screen
     * in one call instead of chaining GET /sections.
     */
    public function show(Page $page): JsonResponse
    {
        $page->load('sections');

        return response()->json([
            'status' => 'success',
            'data' => $this->present($page) + [
                'sections' => $page->sections->map(fn (Section $section) => [
                    'id' => $section->id,
                    'type' => $section->type,
                    'position' => $section->position,
                    'content' => $section->content,
                ])->all(),
            ],
            'meta' => [],
        ]);
    }

    /**
     * Publish or unpublish a page. Nothing else about a page is editable.
     */
    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $page->update($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $this->present($page),
            'meta' => [
                'message' => $page->is_published
                    ? 'Página publicada correctamente'
                    : 'Página despublicada correctamente',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Page $page): array
    {
        return [
            'id' => $page->id,
            'site_id' => $page->site_id,
            'parent_id' => $page->parent_id,
            'slug' => $page->slug,
            'path' => $page->path,
            'title' => $page->title,
            'position' => $page->position,
            'is_published' => $page->is_published,
        ];
    }
}
