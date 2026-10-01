<?php

namespace Database\Seeders;

use App\Models\InterviewDay;
use Illuminate\Database\Seeder;

class InterviewDaySeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            '2026-10-03',
            '2026-10-04',
        ];

        foreach ($days as $date) {
            $day = InterviewDay::create([
                'date' => $date,
                'starts_at' => '08:00:00',
                'slot_minutes' => 10,
                'room' => 'DC-302',
            ]);

            $day->breakBlocks()->createMany([
                [
                    'label' => 'Dzuhur',
                    'starts_at' => '11:45:00',
                    'duration_minutes' => 60,
                ],
                [
                    'label' => 'Ashar',
                    'starts_at' => '15:00:00',
                    'duration_minutes' => 60,
                ],
            ]);
        }
    }
}
