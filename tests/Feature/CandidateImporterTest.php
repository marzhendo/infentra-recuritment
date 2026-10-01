<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\ImportLog;
use App\Services\CandidateImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Database\Seeders\DivisionSeeder;

class CandidateImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(DivisionSeeder::class);
        
        Storage::fake('local');
    }

    private function getFixtureCsvContent(string $bom = ''): string
    {
        // First line includes an embedded newline in one of the headers to test the requirement
        $header = "Timestamp,Nama Lengkap,Angkatan,\"Pilihan 1\n\",Pilihan 2,\"Sertifikat PKKMB/Bukti keikutsertaan\",\"Sertifikat WPI/Bukti keikutsertaan\",CV,WhatsApp,Email Address,Portofolio,NIM";
        
        $rows = [
            // Normal row with single digit hour/day
            '"3/9/2026 8:15:30"," Budi Santoso ","2024","Usdakom","Sponsor","http://drive/pkkmb1","http://drive/wpi1","http://drive/cv1","08123456789","email@test.com","http://drive/port1","13524001"',
            
            // Resubmission of Budi with later timestamp and different file (same name, same WA after normalization)
            '"29/10/2026 23:59:59","budi santoso","2024","Usdakom","Sponsor","http://drive/pkkmb2","http://drive/wpi2","http://drive/cv2","+628123456789","","",""',
            
            // Same name, different WhatsApp -> TWO candidates
            '"4/9/2026 12:00:00","Budi Santoso","2024","Usdakom","Sponsor","http://drive/pkkmb3","http://drive/wpi3","http://drive/cv3","628999999999","","","13524002"',
            
            // Unknown division -> skip row error
            '"5/9/2026 12:00:00","Citra","2025","UnknownDiv","Sponsor","","","","08111111111","","",""',
        ];

        return $bom . $header . "\n" . implode("\n", $rows) . "\n\n"; // blank trailing lines
    }

    public function test_importing_same_file_twice_gives_zero_created_on_second_run()
    {
        $csv = $this->getFixtureCsvContent();
        Storage::disk('local')->put('test.csv', $csv);
        $path = Storage::disk('local')->path('test.csv');

        $importer = new CandidateImporter();
        
        $result1 = $importer->import($path);
        
        $this->assertEquals(2, $result1['created']); // 2 valid unique candidates (Budi Santoso 1, Budi Santoso 2)
        $this->assertEquals(0, $result1['updated']);
        $this->assertEquals(1, $result1['errors_count']); // Citra (UnknownDiv)
        
        // Second run
        $result2 = $importer->import($path);
        
        $this->assertEquals(0, $result2['created']);
        $this->assertEquals(2, $result2['updated']); // Will "update" but actually same data
        $this->assertEquals(1, $result2['errors_count']);
    }

    public function test_bom_file_handled()
    {
        $bom = "\xEF\xBB\xBF";
        $csv = $this->getFixtureCsvContent($bom);
        Storage::disk('local')->put('test_bom.csv', $csv);
        $path = Storage::disk('local')->path('test_bom.csv');

        $importer = new CandidateImporter();
        $result = $importer->import($path);

        $this->assertEquals(2, $result['created']);
    }

    public function test_resubmission_logic_and_nim_preservation()
    {
        $csv = $this->getFixtureCsvContent();
        Storage::disk('local')->put('test.csv', $csv);
        
        $importer = new CandidateImporter();
        $importer->import(Storage::disk('local')->path('test.csv'));
        
        $candidate = Candidate::where('whatsapp', '628123456789')->first();
        $this->assertNotNull($candidate);
        
        // The later timestamp wins for all form fields
        $this->assertEquals('http://drive/cv2', $candidate->file_cv);
        
        // But NIM is kept from earlier row since it's blank in the later one
        $this->assertEquals('13524001', $candidate->nim);
        
        // Check previous_submissions inside form_data
        $this->assertIsArray($candidate->form_data['previous_submissions'] ?? null);
        $this->assertCount(1, $candidate->form_data['previous_submissions']);
    }
    
    public function test_is_hmif_survives_reimport()
    {
        $csv = $this->getFixtureCsvContent();
        Storage::disk('local')->put('test.csv', $csv);
        
        $importer = new CandidateImporter();
        $importer->import(Storage::disk('local')->path('test.csv'));
        
        $candidate = Candidate::where('whatsapp', '628123456789')->first();
        $candidate->update(['is_hmif' => true]);
        
        // Reimport
        $importer->import(Storage::disk('local')->path('test.csv'));
        
        $candidate->refresh();
        $this->assertTrue($candidate->is_hmif);
    }
}
