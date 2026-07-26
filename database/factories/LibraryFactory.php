<?php

namespace Database\Factories;

use App\LibraryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class LibraryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'base_path' => fake()->filePath(),
            'status' => fake()->randomElement(LibraryStatus::cases())->value,
            'scan_interval' => fake()->numberBetween(60, 86400),
            'last_scan' => fake()->optional()->dateTime(),
        ];
    }
}
