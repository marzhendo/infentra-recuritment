<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'starts_at' => fake()->time(),
            'ends_at' => fake()->time(),
            'room' => 'DC-302',
        ];
    }
}
