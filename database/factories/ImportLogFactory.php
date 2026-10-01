<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'imported_at' => now(),
            'created_count' => fake()->randomDigit(),
            'updated_count' => 0,
            'user_id' => User::factory(),
        ];
    }
}
