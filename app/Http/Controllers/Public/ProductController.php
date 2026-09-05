<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only product catalogue. Sites without products simply have none, so no
 * per-site flag is needed here — the listing just comes back empty.
 *
 * Categories are not pages of the blueprint: `/productos` exists and carries
 * only a `page_hero`, exactly like `/blog`. The front builds the category
 * submenu from `categories()`, since `GET {site}/menu` derives from `pages`.
 */
class ProductController extends Controller
{
    /**
     * List the categories of the site, in blueprint order, each with how many
     * active products it holds.
     */
    public function categories(Site $site): JsonResponse
    {
        $categories = $site->productCategories()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('position')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories->map(fn (ProductCategory $category) => [
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'image' => $category->image,
                'products_count' => $category->products_count,
            ])->all(),
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * List active products, optionally narrowed by category and search term.
     */
    public function index(Site $site, Request $request): JsonResponse
    {
        $search = $request->string('q')->trim()->value();

        $products = $site->products()
            ->active()
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->whereHas(
                'category',
                fn ($category) => $category->where('slug', $request->string('category'))
            ))
            // ponytail: plain LIKE, no accent folding. FTS5 if the catalogue
            // ever grows past a few hundred rows.
            ->when($search !== '', fn ($query) => $query->where(
                fn ($match) => $match->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
            ->with('category:id,slug,name')
            ->orderBy('name')
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => collect($products->items())->map(fn (Product $product) => $this->present($product))->all(),
            'meta' => [
                'site' => $site->slug,
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Return a single active product by slug.
     */
    public function show(Site $site, string $productSlug): JsonResponse
    {
        $product = $site->products()
            ->active()
            ->with('category:id,slug,name')
            ->where('slug', $productSlug)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $this->present($product, full: true),
            'meta' => ['site' => $site->slug],
        ]);
    }

    /**
     * The listing carries only the first image and no full description: same
     * criterion as the blog listing, which omits the body.
     *
     * `price` is null when the product is quoted rather than listed.
     *
     * @return array<string, mixed>
     */
    private function present(Product $product, bool $full = false): array
    {
        $data = [
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $product->price,
            'category' => [
                'slug' => $product->category->slug,
                'name' => $product->category->name,
            ],
        ];

        if (! $full) {
            return [...$data, 'image' => $product->images[0] ?? null];
        }

        return [...$data, 'description' => $product->description, 'images' => $product->images];
    }
}
