<?php

namespace Database\Factories;

use App\Models\Division;
use App\Enums\CandidateStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();
        $whatsapp = '62' . fake()->numerify('812########');

        return [
            'import_key' => strtolower($name) . '|' . $whatsapp,
            'name' => $name,
            'whatsapp' => $whatsapp,
            'nim' => fake()->numerify('1352####'),
            'angkatan' => '2023',
            'pilihan_1_id' => Division::factory(),
            'pilihan_2_id' => Division::factory(),
            'form_timestamp' => now(),
            'form_data' => [],
            'status' => CandidateStatus::Terdaftar,
            'is_hmif' => false,
        ];
    }
}
