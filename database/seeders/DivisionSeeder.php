<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            ['name' => 'Acara'],
            ['name' => 'Keamanan'],
            ['name' => 'Danus & Konsum', 'aliases' => ['Usdakom']],
            ['name' => 'Humas'],
            ['name' => 'Perkap'],
            ['name' => 'PDD'],
            ['name' => 'Sponsor', 'aliases' => ['Sponsorship']],
            ['name' => 'IT Team'],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['name' => $division['name']],
                ['aliases' => $division['aliases'] ?? null]
            );
        }
    }
}
