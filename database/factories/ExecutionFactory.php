<?php

namespace Database\Factories;

use App\ExecutionStatus;
use App\Models\LibraryJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExecutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'library_job_id' => LibraryJob::factory(),
            'worker_id' => fake()->uuid(),
            'file_path' => fake()->filePath(),
            'status' => fake()->randomElement(ExecutionStatus::cases())->value,
            'started_at' => fake()->optional()->dateTime(),
            'finished_at' => fake()->optional()->dateTime(),
        ];
    }
}
