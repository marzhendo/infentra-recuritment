<?php

namespace Database\Factories;

use App\Models\InterviewDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'interview_day_id' => InterviewDay::factory(),
            'label' => 'Break',
            'starts_at' => '12:00:00',
            'duration_minutes' => 60,
        ];
    }
}
