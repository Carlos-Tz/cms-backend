<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'message' => fake()->sentence(10),
        ];
    }
}
