<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates a product on both create and update.
 *
 * Update takes the full payload (PUT semantics) so there is no separate request
 * class. `site_id` is only accepted on create: a product never moves between
 * sites, same rule as `BlogPostRequest`.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Derive the slug from the name when the admin does not send one.
     */
    protected function prepareForValidation(): void
    {
        if ($this->missing('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->string('name')->value())]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->product();
        $siteId = $product?->site_id ?? $this->integer('site_id');

        return [
            'site_id' => $product === null
                ? ['required', 'integer', 'exists:sites,id']
                : ['prohibited'],
            // Scoped to the site: a category of another site is a 422, never an
            // orphan product. It is the only cross-table check here.
            'product_category_id' => [
                'required', 'integer',
                Rule::exists('product_categories', 'id')->where('site_id', $siteId),
            ],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('products')->where('site_id', $siteId)->ignore($product),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            // Nullable: null means the product is quoted, not listed.
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            // Paths returned by POST /api/admin/media, same as section images.
            'images' => ['nullable', 'array'],
            'images.*' => ['string', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * An absent `images` key means «sin imágenes», not «conserva las anteriores»:
     * this request takes the full payload on update too.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated();

        return [...$validated, 'images' => $validated['images'] ?? []];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sitio',
            'product_category_id' => 'categoría',
            'slug' => 'slug',
            'name' => 'nombre',
            'description' => 'descripción',
            'price' => 'precio',
            'images' => 'imágenes',
            'is_active' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Ya existe un producto con ese slug en este sitio.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'site_id.prohibited' => 'Un producto no puede cambiar de sitio.',
            'product_category_id.exists' => 'La categoría no pertenece a este sitio.',
        ];
    }

    /**
     * The product being updated, or null when creating.
     */
    private function product(): ?Product
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return $product;
    }
}
