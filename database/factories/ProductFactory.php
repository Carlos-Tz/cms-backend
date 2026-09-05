<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Neither `site_id` nor `product_category_id` has a default: the caller
     * passes both with `->for($site)->for($category, 'category')`, same reason as
     * `AppointmentFactory`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::ucfirst($name),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1000, 500000),
            'images' => ['media/placeholder-producto.jpg'],
            'is_active' => true,
        ];
    }

    /**
     * Hidden from the public API but kept in the panel.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * Sold on quotation: the front shows «Precio a cotizar».
     */
    public function quoted(): static
    {
        return $this->state(['price' => null]);
    }
}
