<?php

namespace Database\Factories;

use App\Models\InterviewDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'interview_day_id' => InterviewDay::factory(),
            'starts_at' => fake()->time(),
            'ends_at' => fake()->time(),
            'is_locked' => false,
        ];
    }
}
