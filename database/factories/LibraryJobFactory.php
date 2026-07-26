<?php

namespace Database\Factories;

use App\LibraryJobId;
use App\Models\Library;
use Illuminate\Database\Eloquent\Factories\Factory;

class LibraryJobFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'library_id' => Library::factory(),
            'job_id' => fake()->randomElement(LibraryJobId::cases())->value,
        ];
    }
}
