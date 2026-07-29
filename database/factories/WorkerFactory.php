<?php

namespace Database\Factories;

use App\LibraryJobId;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'job_type' => fake()->randomElement(LibraryJobId::cases()),
            'concurrency' => fake()->numberBetween(1, 4),
            'replace_original' => false,
            'enabled' => true,
        ];
    }
}
