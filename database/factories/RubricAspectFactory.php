<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RubricAspectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'position' => fake()->randomDigit(),
            'is_active' => true,
        ];
    }
}
