<?php

namespace Database\Factories;

use App\Models\LibraryJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExecutionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'library_job_id' => LibraryJob::factory(),
            'worker_id' => fake()->word(),
            'file_path' => fake()->word(),
            'status' => fake()->word(),
            'started_at' => fake()->dateTime(),
            'finished_at' => fake()->dateTime(),
        ];
    }
}
