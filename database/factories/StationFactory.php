<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Station>
 */
class StationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * There is no `site_id` default: `Site` has no factory (sites come from the
     * blueprint seeders), so the caller passes the site with `->for($site)`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Estación '.fake()->city(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            // Roughly within Mexico, so seeded pins land on the map.
            'latitude' => fake()->randomFloat(7, 16, 31),
            'longitude' => fake()->randomFloat(7, -116, -88),
            'phone' => fake()->numerify('+52 ## #### ####'),
            'schedule' => 'Lunes a domingo de 6:00 a 22:00',
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
}
