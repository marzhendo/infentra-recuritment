<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlacementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'division_id' => Division::factory(),
            'final_status' => 'lolos',
            'set_by' => User::factory(),
        ];
    }
}
