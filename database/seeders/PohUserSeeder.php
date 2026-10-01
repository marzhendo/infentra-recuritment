<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PohUserSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/poh.csv');
        if (! File::exists($path)) {
            $this->command->warn('File seeders/data/poh.csv is missing. Skipping PohUserSeeder.');

            return;
        }

        $file = fopen($path, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            if (count($headers) !== count($row)) {
                continue;
            }
            $data = array_combine($headers, $row);

            $divisionId = null;
            if (! empty($data['division'])) {
                $divisionName = $data['division'];
                $division = Division::where('name', $divisionName)->orWhereJsonContains('aliases', $divisionName)->first();
                if ($division) {
                    $divisionId = $division->id;
                }
            }

            User::updateOrCreate(
                ['nim' => $data['nim']],
                [
                    'name' => $data['nama'],
                    'jabatan' => $data['jabatan'],
                    'role' => $data['role'],
                    'division_id' => $divisionId,
                    'is_head_interviewer' => $data['jabatan'] === 'Ketua Pelaksana',
                ]
            );
        }
        fclose($file);
    }
}
