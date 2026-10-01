<?php

namespace Database\Factories;

use App\Models\InterviewSlot;
use App\Models\User;
use App\Models\RubricAspect;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slot_id' => InterviewSlot::factory(),
            'interviewer_id' => User::factory(),
            'rubric_aspect_id' => RubricAspect::factory(),
            'value' => fake()->numberBetween(1, 5),
            'note' => fake()->sentence(),
        ];
    }
}
