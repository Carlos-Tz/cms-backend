<?php

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * No `site_id` or `station_id` defaults: both come from the blueprint, so
     * the caller passes them with `->for($site)` and `->for($station)`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->next('Monday')->toDateString(),
            'slot' => fake()->randomElement(Appointment::SLOTS),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+52 ### ### ####'),
            'vehicle_plate' => strtoupper(fake()->bothify('???-##-##')),
            'vehicle_description' => fake()->randomElement(['Nissan Versa 2020', 'Chevrolet Aveo 2018', null]),
        ];
    }
}
