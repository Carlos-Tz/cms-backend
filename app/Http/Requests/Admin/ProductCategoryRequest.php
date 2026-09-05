<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates a product category on both create and update.
 *
 * Update takes the full payload (PUT semantics) so there is no separate request
 * class. `site_id` is only accepted on create: a category never moves between
 * sites, same rule as `BlogPostRequest`.
 */
class ProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Derive the slug from the name when the admin does not send one.
     *
     * A generated slug that collides fails validation instead of getting a `-2`
     * suffix: the admin decides the final URL of a category.
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
        $category = $this->productCategory();

        return [
            'site_id' => $category === null
                ? ['required', 'integer', 'exists:sites,id']
                : ['prohibited'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('product_categories')->where(
                    'site_id',
                    $category?->site_id ?? $this->integer('site_id'),
                )->ignore($category),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Path returned by POST /api/admin/media, same as section images.
            'image' => ['nullable', 'string', 'max:2048'],
            'position' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sitio',
            'slug' => 'slug',
            'name' => 'nombre',
            'description' => 'descripción',
            'image' => 'imagen',
            'position' => 'posición',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Ya existe una categoría con ese slug en este sitio.',
            'slug.regex' => 'El slug solo admite minúsculas, números y guiones.',
            'site_id.prohibited' => 'Una categoría no puede cambiar de sitio.',
        ];
    }

    /**
     * The category being updated, or null when creating.
     */
    private function productCategory(): ?ProductCategory
    {
        /** @var ProductCategory|null $category */
        $category = $this->route('product_category');

        return $category;
    }
}
