<?php

namespace Database\Factories;

use App\Models\Division;
use App\Enums\CandidateStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nim' => fake()->numerify('1352####'),
            'angkatan' => '2023',
            'pilihan_1_id' => Division::factory(),
            'pilihan_2_id' => Division::factory(),
            'form_timestamp' => now()->toDateTimeString(),
            'status' => CandidateStatus::Terdaftar,
            'is_hmif' => false,
        ];
    }
}
