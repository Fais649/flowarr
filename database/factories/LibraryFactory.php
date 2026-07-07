<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LibraryFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'base_path' => fake()->word(),
            'status' => fake()->word(),
            'scan_interval' => fake()->numberBetween(-10000, 10000),
            'last_scan' => fake()->dateTime(),
        ];
    }
}
