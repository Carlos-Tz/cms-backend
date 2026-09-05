<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * No `site_id` default: `Site` has no factory (sites come from the blueprint
     * seeders), so the caller passes the site with `->for($site)`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::ucfirst($name),
            'description' => fake()->sentence(),
            'image' => null,
            'position' => 1,
        ];
    }
}
