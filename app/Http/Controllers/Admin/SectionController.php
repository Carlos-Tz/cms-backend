<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sections are structure: the admin may only edit their `content`.
 * Creating, deleting and reordering sections belongs to the blueprint,
 * so this controller exposes index / show / update only.
 */
class SectionController extends Controller
{
    /**
     * List sections, optionally filtered by site slug, page slug or globals only.
     */
    public function index(Request $request): JsonResponse
    {
        $sections = Section::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->when($request->string('page')->isNotEmpty(), fn ($query) => $query->whereHas(
                'page',
                fn ($page) => $page->where('slug', $request->string('page'))
            ))
            ->when($request->boolean('global'), fn ($query) => $query->whereNull('page_id'))
            ->orderBy('page_id')
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sections,
            'meta' => ['total' => $sections->count()],
        ]);
    }

    public function show(Section $section): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $section,
            'meta' => [],
        ]);
    }

    /**
     * Replace the editable content of a section.
     *
     * Locked (`fijo`) fields are not part of the validated payload, so merging
     * over the stored content keeps them untouched.
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $section->update([
            'content' => array_merge($section->content, $request->validated('content')),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $section->fresh(),
            'meta' => ['message' => 'Sección actualizada correctamente'],
        ]);
    }
}
