<?php

namespace Database\Factories;

use App\LibraryJobId;
use App\Models\JobWorkerLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobWorkerLimit> */
class JobWorkerLimitFactory extends Factory
{
    protected $model = JobWorkerLimit::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'job_type' => fake()->randomElement(LibraryJobId::cases())->value,
            'max_concurrent' => fake()->numberBetween(1, 5),
        ];
    }
}
