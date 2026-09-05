<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Database\Seeders\TectuusBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

// `tectuusSection()` lives in TectuusHomeTest: Pest helpers are global.

beforeEach(function () {
    $this->seed(TectuusBlueprintSeeder::class);
    $this->site = Site::where('slug', 'tectuus')->firstOrFail();
    $this->edrm = $this->site->productCategories()->where('slug', 'edrm')->firstOrFail();
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * Make a product of the given category, since the factory defaults neither
 * `site_id` nor `product_category_id`.
 *
 * @param  array<string, mixed>  $attributes
 */
function tectuusProduct(ProductCategory $category, array $attributes = []): Product
{
    return Product::factory()
        ->for($category->site)
        ->for($category, 'category')
        ->create($attributes);
}

it('returns the products page with only its hero', function () {
    $this->getJson('/api/public/tectuus/pages/productos')
        ->assertOk()
        ->assertJsonPath('data.title', 'Productos')
        ->assertJsonPath('data.path', '/productos')
        ->assertJsonCount(1, 'data.sections')
        ->assertJsonPath('data.sections.0.type', 'page_hero');
});

/**
 * The categories are rows, not pages, so the derived menu shows the column
 * without children — same as `/blog` in the other two sites.
 */
it('lists productos as the third column with no child pages', function () {
    $this->getJson('/api/public/tectuus/menu')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.2.title', 'Productos')
        ->assertJsonCount(0, 'data.2.children');
});

it('returns the three seeded categories in blueprint order', function () {
    tectuusProduct($this->edrm);
    tectuusProduct($this->edrm, ['is_active' => false]);

    $this->getJson('/api/public/tectuus/product-categories')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'edrm')
        ->assertJsonPath('data.1.slug', 'compresoras')
        ->assertJsonPath('data.2.slug', 'valvuleria')
        // Only the active one counts: the inactive product is invisible here too.
        ->assertJsonPath('data.0.products_count', 1)
        ->assertJsonPath('data.1.products_count', 0);
});

it('lists only the active products', function () {
    tectuusProduct($this->edrm, ['name' => 'Estación EDRM 500', 'slug' => 'estacion-edrm-500']);
    tectuusProduct($this->edrm, ['name' => 'Estación EDRM 900', 'slug' => 'estacion-edrm-900', 'is_active' => false]);

    $this->getJson('/api/public/tectuus/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'estacion-edrm-500')
        ->assertJsonPath('data.0.category.slug', 'edrm')
        // The listing carries one image and no description, like the blog feed.
        ->assertJsonMissingPath('data.0.description');
});

it('filters the products by category', function () {
    $compresoras = $this->site->productCategories()->where('slug', 'compresoras')->firstOrFail();

    tectuusProduct($this->edrm, ['name' => 'Estación EDRM 500', 'slug' => 'estacion-edrm-500']);
    tectuusProduct($compresoras, ['name' => 'Compresora CX-1', 'slug' => 'compresora-cx-1']);

    $this->getJson('/api/public/tectuus/products?category=compresoras')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'compresora-cx-1');
});

it('searches the products by name and description', function () {
    tectuusProduct($this->edrm, [
        'name' => 'Estación EDRM 500',
        'slug' => 'estacion-edrm-500',
        'description' => 'Regulación y medición de alta presión.',
    ]);
    tectuusProduct($this->edrm, ['name' => 'Válvula de bola', 'slug' => 'valvula-de-bola']);

    $this->getJson('/api/public/tectuus/products?q=EDRM')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'estacion-edrm-500');

    $this->getJson('/api/public/tectuus/products?q=medición')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'estacion-edrm-500');
});

it('returns a single product with its images and category', function () {
    tectuusProduct($this->edrm, [
        'slug' => 'estacion-edrm-500',
        'description' => 'Regulación y medición de alta presión.',
        'images' => ['media/edrm-1.jpg', 'media/edrm-2.jpg'],
    ]);

    $this->getJson('/api/public/tectuus/products/estacion-edrm-500')
        ->assertOk()
        ->assertJsonPath('data.description', 'Regulación y medición de alta presión.')
        ->assertJsonCount(2, 'data.images')
        ->assertJsonPath('data.category.name', 'EDRM')
        ->assertJsonPath('meta.site', 'tectuus');
});

it('hides an inactive product from the public API', function () {
    tectuusProduct($this->edrm, ['slug' => 'estacion-edrm-500', 'is_active' => false]);

    $this->getJson('/api/public/tectuus/products/estacion-edrm-500')->assertNotFound();
});

/**
 * `price` is nullable on purpose: industrial equipment is quoted more often
 * than listed, and the front shows «Precio a cotizar» when it comes back null.
 */
it('returns a null price for a quoted product', function () {
    tectuusProduct($this->edrm, ['slug' => 'compresora-a-cotizar', 'price' => null]);

    $this->getJson('/api/public/tectuus/products/compresora-a-cotizar')
        ->assertOk()
        ->assertJsonPath('data.price', null);
});

it('keeps the products of another site out of the listing', function () {
    $this->seed(GnuBlueprintSeeder::class);

    $gnu = Site::where('slug', 'gnu')->firstOrFail();
    $foreign = ProductCategory::factory()->for($gnu)->create(['slug' => 'otra']);
    tectuusProduct($foreign, ['slug' => 'producto-ajeno']);
    tectuusProduct($this->edrm, ['slug' => 'estacion-edrm-500']);

    $this->getJson('/api/public/tectuus/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'estacion-edrm-500');
});

it('creates, updates and deletes a product through the admin API', function () {
    $created = $this->withToken($this->token)
        ->postJson('/api/admin/products', [
            'site_id' => $this->site->id,
            'product_category_id' => $this->edrm->id,
            'name' => 'Estación EDRM 500',
            'description' => 'Regulación y medición de alta presión.',
            'price' => 189500.50,
            'images' => ['media/edrm-1.jpg'],
            'is_active' => true,
        ])
        ->assertCreated()
        // The slug is derived from the name when the admin does not send one.
        ->assertJsonPath('data.slug', 'estacion-edrm-500')
        ->json('data.id');

    $this->withToken($this->token)
        ->putJson("/api/admin/products/{$created}", [
            'product_category_id' => $this->edrm->id,
            'slug' => 'estacion-edrm-500',
            'name' => 'Estación EDRM 500 Plus',
            'description' => 'Regulación y medición de alta presión.',
            'price' => null,
            'images' => [],
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Estación EDRM 500 Plus')
        ->assertJsonPath('data.price', null);

    $this->withToken($this->token)
        ->deleteJson("/api/admin/products/{$created}")
        ->assertOk();

    expect(Product::find($created))->toBeNull();
});

it('lists the products of a site including the inactive ones', function () {
    tectuusProduct($this->edrm, ['slug' => 'activo']);
    tectuusProduct($this->edrm, ['slug' => 'inactivo', 'is_active' => false]);

    $this->withToken($this->token)
        ->getJson('/api/admin/products?site=tectuus')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('rejects a product filed under a category of another site', function () {
    $this->seed(GnuBlueprintSeeder::class);

    $gnu = Site::where('slug', 'gnu')->firstOrFail();
    $foreign = ProductCategory::factory()->for($gnu)->create(['slug' => 'otra']);

    $this->withToken($this->token)
        ->postJson('/api/admin/products', [
            'site_id' => $this->site->id,
            'product_category_id' => $foreign->id,
            'name' => 'Producto imposible',
            'description' => 'No debería existir.',
            'price' => 100,
            'images' => [],
            'is_active' => true,
        ])
        ->assertJsonValidationErrors('product_category_id');
});

it('does not let a product change site', function () {
    $product = tectuusProduct($this->edrm, ['slug' => 'estacion-edrm-500']);

    $this->withToken($this->token)
        ->putJson("/api/admin/products/{$product->id}", [
            'site_id' => $this->site->id,
            'product_category_id' => $this->edrm->id,
            'slug' => 'estacion-edrm-500',
            'name' => 'Estación EDRM 500',
            'description' => 'Regulación y medición.',
            'price' => 100,
            'images' => [],
            'is_active' => true,
        ])
        ->assertJsonValidationErrors('site_id');
});

/**
 * The FK is `restrictOnDelete`; this guard is what turns it into a readable
 * answer instead of an engine error.
 */
it('refuses to delete a category that still has products', function () {
    tectuusProduct($this->edrm, ['slug' => 'estacion-edrm-500']);

    $this->withToken($this->token)
        ->deleteJson("/api/admin/product-categories/{$this->edrm->id}")
        ->assertStatus(409)
        ->assertJsonPath('status', 'error');

    expect(ProductCategory::find($this->edrm->id))->not->toBeNull();
});

it('deletes an empty category', function () {
    $this->withToken($this->token)
        ->deleteJson("/api/admin/product-categories/{$this->edrm->id}")
        ->assertOk();

    expect(ProductCategory::find($this->edrm->id))->toBeNull();
});

it('blocks the catalogue admin api without a token', function () {
    $this->getJson('/api/admin/products')->assertUnauthorized();
    $this->getJson('/api/admin/product-categories')->assertUnauthorized();
});
