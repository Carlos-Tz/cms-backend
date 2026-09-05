<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product categories are data, not blueprint structure: full CRUD, like blog
 * posts. The blueprint seeds the three of Tectuus; the admin manages them from
 * there on.
 */
class ProductCategoryController extends Controller
{
    /**
     * List categories, optionally filtered by site slug.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = ProductCategory::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->withCount('products')
            ->orderBy('position')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $categories->items(),
            'meta' => [
                'page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    public function store(ProductCategoryRequest $request): JsonResponse
    {
        $category = ProductCategory::create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $category,
            'meta' => ['message' => 'Categoría creada correctamente'],
        ], 201);
    }

    public function show(ProductCategory $productCategory): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $productCategory->loadCount('products'),
            'meta' => [],
        ]);
    }

    public function update(ProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $productCategory->update($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $productCategory->fresh(),
            'meta' => ['message' => 'Categoría actualizada correctamente'],
        ]);
    }

    /**
     * Deleting a category with products is a 409, not a cascade: the products
     * would disappear in silence. The FK is `restrictOnDelete`, so this check is
     * what turns the engine error into a readable answer.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        if ($productCategory->products()->exists()) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'meta' => ['message' => 'La categoría tiene productos asociados. Muévelos o elimínalos primero.'],
            ], 409);
        }

        $productCategory->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Categoría eliminada correctamente'],
        ]);
    }
}
