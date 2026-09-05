<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Products are data, not blueprint structure: full CRUD, like blog posts.
 * Inactive products are listed here but never in the public API, the same way
 * drafts are listed in the blog panel.
 */
class ProductController extends Controller
{
    /**
     * List products, optionally filtered by site slug and category slug.
     * Inactive included.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->when($request->string('site')->isNotEmpty(), fn ($query) => $query->whereHas(
                'site',
                fn ($site) => $site->where('slug', $request->string('site'))
            ))
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->whereHas(
                'category',
                fn ($category) => $category->where('slug', $request->string('category'))
            ))
            // Eager loaded: the panel shows the category name on every row.
            ->with('category:id,slug,name')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $products->items(),
            'meta' => [
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $product,
            'meta' => ['message' => 'Producto creado correctamente'],
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $product->load('category:id,slug,name'),
            'meta' => [],
        ]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => $product->fresh(),
            'meta' => ['message' => 'Producto actualizado correctamente'],
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'meta' => ['message' => 'Producto eliminado correctamente'],
        ]);
    }
}
