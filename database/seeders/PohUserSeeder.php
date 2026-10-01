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
        
        if (File::exists($path)) {
            $csvContent = File::get($path);
        } elseif (env('POH_CSV_BASE64')) {
            $csvContent = base64_decode(env('POH_CSV_BASE64'));
        } else {
            $this->command->warn('File seeders/data/poh.csv is missing and POH_CSV_BASE64 env is not set. Skipping PohUserSeeder.');
            return;
        }

        $lines = explode("\n", str_replace("\r", "", trim($csvContent)));
        if (count($lines) < 2) return;
        
        $headers = str_getcsv(array_shift($lines));

        foreach ($lines as $line) {
            $row = str_getcsv($line);
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
    }
}
