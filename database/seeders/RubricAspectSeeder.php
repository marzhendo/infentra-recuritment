<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RubricAspect;

class RubricAspectSeeder extends Seeder
{
    public function run(): void
    {
        $aspects = [
            'Komitmen & Tanggung Jawab',
            'Komunikasi',
            'Problem Solving',
            'Kerja Sama Tim',
            'Inisiatif & Proaktif',
            'Ketersediaan Waktu',
            'Motivasi & Pemahaman Peran',
        ];

        foreach ($aspects as $index => $aspect) {
            RubricAspect::firstOrCreate(
                ['name' => $aspect],
                ['position' => $index + 1, 'is_active' => true]
            );
        }
    }
}
