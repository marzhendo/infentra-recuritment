<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\ImportLog;
use Carbon\Carbon;
use Illuminate\Support\Str;
use League\Csv\Reader;

class CandidateImporter
{
    private array $divisionCache = [];

    public function import(string $filePath): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        // Handle BOM
        $csv->setHeaderOffset(0);
        $csv->skipEmptyRecords();
        
        $records = $csv->getRecords();
        $header = $csv->getHeader();
        
        // Clean header (first line, trimmed, case-insensitive)
        $cleanHeader = [];
        foreach ($header as $col) {
            $firstLine = explode("\n", $col)[0];
            $cleanHeader[] = strtolower(trim($firstLine));
        }
        
        $colIndex = array_flip($cleanHeader);
        
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors_count' => 0,
            'errors' => [],
        ];

        // Group rows by import_key
        $grouped = [];

        foreach ($records as $index => $row) {
            // Re-key the row using the clean header
            $cleanRow = [];
            foreach ($header as $i => $originalHeader) {
                if (isset($cleanHeader[$i])) {
                    $cleanRow[$cleanHeader[$i]] = trim($row[$originalHeader] ?? '');
                }
            }
            
            $name = $cleanRow['nama lengkap'] ?? '';
            $whatsapp = $this->normalizeWhatsapp($cleanRow['whatsapp'] ?? '');
            
            $importKey = $this->generateImportKey($name, $whatsapp);
            
            $cleanRow['_line'] = $index;
            $grouped[$importKey][] = $cleanRow;
        }

        foreach ($grouped as $importKey => $rows) {
            // Sort by timestamp descending
            usort($rows, function ($a, $b) {
                $timeA = $this->parseTimestamp($a['timestamp'] ?? '');
                $timeB = $this->parseTimestamp($b['timestamp'] ?? '');
                return $timeB <=> $timeA;
            });

            $latestRow = $rows[0];
            $latestTimestamp = $this->parseTimestamp($latestRow['timestamp'] ?? '');
            
            $p1 = $latestRow['pilihan 1'] ?? '';
            $p2 = $latestRow['pilihan 2'] ?? '';
            
            $div1 = $this->resolveDivision($p1);
            $div2 = $this->resolveDivision($p2);
            
            if (!$div1 || !$div2) {
                $summary['errors_count']++;
                $summary['errors'][] = "Line {$latestRow['_line']}: Unknown division ($p1, $p2)";
                $summary['skipped']++;
                continue;
            }

            // Find best NIM across all rows
            $bestNim = null;
            foreach ($rows as $r) {
                if (!empty($r['nim'])) {
                    $bestNim = $r['nim'];
                    break;
                }
            }

            $candidate = Candidate::where('import_key', $importKey)->first();

            $formData = $latestRow;
            unset($formData['_line']);
            
            if (count($rows) > 1) {
                $formData['previous_submissions'] = array_slice($rows, 1);
            }

            if ($candidate) {
                // Keep NIM if latest row has none but candidate already has one, or use best Nim from grouped rows
                $finalNim = $bestNim ?: $candidate->nim;
                
                // Keep previous submissions from existing form_data if they exist
                $existingPrevious = $candidate->form_data['previous_submissions'] ?? [];
                if (isset($formData['previous_submissions'])) {
                    $formData['previous_submissions'] = array_merge($formData['previous_submissions'], $existingPrevious);
                } else if (!empty($existingPrevious)) {
                    $formData['previous_submissions'] = $existingPrevious;
                }

                $candidate->update([
                    'name' => $latestRow['nama lengkap'],
                    'whatsapp' => $this->normalizeWhatsapp($latestRow['whatsapp'] ?? ''),
                    'nim' => $finalNim,
                    'angkatan' => $latestRow['angkatan'] ?? null,
                    'pilihan_1_id' => $div1,
                    'pilihan_2_id' => $div2,
                    'file_cert_pkkmb' => $latestRow['sertifikat pkkmb/bukti keikutsertaan'] ?? null,
                    'file_cert_wpi' => $latestRow['sertifikat wpi/bukti keikutsertaan'] ?? null,
                    'file_cv' => $latestRow['cv'] ?? null,
                    'file_portfolio' => $latestRow['portofolio'] ?? null,
                    'form_timestamp' => $latestTimestamp,
                    'form_data' => $formData,
                ]);
                $summary['updated']++;
            } else {
                Candidate::create([
                    'import_key' => $importKey,
                    'name' => $latestRow['nama lengkap'],
                    'whatsapp' => $this->normalizeWhatsapp($latestRow['whatsapp'] ?? ''),
                    'nim' => $bestNim,
                    'angkatan' => $latestRow['angkatan'] ?? null,
                    'pilihan_1_id' => $div1,
                    'pilihan_2_id' => $div2,
                    'file_cert_pkkmb' => $latestRow['sertifikat pkkmb/bukti keikutsertaan'] ?? null,
                    'file_cert_wpi' => $latestRow['sertifikat wpi/bukti keikutsertaan'] ?? null,
                    'file_cv' => $latestRow['cv'] ?? null,
                    'file_portfolio' => $latestRow['portofolio'] ?? null,
                    'form_timestamp' => $latestTimestamp,
                    'form_data' => $formData,
                ]);
                $summary['created']++;
            }
        }

        ImportLog::create([
            'imported_at' => now(),
            'created_count' => $summary['created'],
            'updated_count' => $summary['updated'],
        ]);

        return $summary;
    }

    private function normalizeWhatsapp(string $wa): string
    {
        $wa = preg_replace('/[^0-9]/', '', $wa);
        if (str_starts_with($wa, '0')) {
            $wa = '62' . substr($wa, 1);
        }
        return $wa;
    }

    private function generateImportKey(string $name, string $whatsapp): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name));
        $name = strtolower($name);
        
        if (empty($whatsapp)) {
            return $name;
        }
        
        return $name . '|' . $whatsapp;
    }

    private function parseTimestamp(string $ts): ?Carbon
    {
        if (empty($ts)) return null;
        try {
            return Carbon::createFromFormat('j/n/Y G:i:s', trim($ts));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function resolveDivision(string $name): ?int
    {
        if (empty($name)) return null;
        
        $nameLower = strtolower(trim($name));
        
        if (isset($this->divisionCache[$nameLower])) {
            return $this->divisionCache[$nameLower];
        }

        // Search by name
        $div = Division::whereRaw('LOWER(name) = ?', [$nameLower])->first();
        if ($div) {
            $this->divisionCache[$nameLower] = $div->id;
            return $div->id;
        }

        // Search by aliases
        $divisions = Division::all();
        foreach ($divisions as $d) {
            $aliases = $d->aliases ?? [];
            foreach ($aliases as $alias) {
                if (strtolower(trim($alias)) === $nameLower) {
                    $this->divisionCache[$nameLower] = $d->id;
                    return $d->id;
                }
            }
        }

        return null;
    }
}
