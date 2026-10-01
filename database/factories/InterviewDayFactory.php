<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => $this->faker->unique()->date(),
            'starts_at' => '08:00:00',
            'slot_minutes' => 10,
            'room' => 'DC-302',
        ];
    }
}
