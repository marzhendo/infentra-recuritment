<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\User;
use App\Enums\DecisionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DecisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'division_id' => Division::factory(),
            'status' => DecisionStatus::Lolos,
            'decided_by' => User::factory(),
        ];
    }
}
