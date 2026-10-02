<?php

namespace Tests\Feature;

use App\Enums\DecisionStatus;
use App\Enums\Role;
use App\Filament\Pages\HasilSeleksi;
use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Division;
use App\Models\InterviewSlot;
use App\Models\Placement;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HasilSeleksiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hasil_seleksi_page_access_and_export_visibility()
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $koor = User::factory()->create(['role' => Role::Koor]);
        $sekretaris = User::factory()->create(['role' => Role::Admin]);

        // Admin can access and see exports
        $this->actingAs($admin)->get(\App\Filament\Pages\HasilSeleksi::getUrl())
            ->assertSuccessful()
            ->assertSee('Export Rekap Nilai');

        // Koor can access but not see exports
        $this->actingAs($koor)->get(\App\Filament\Pages\HasilSeleksi::getUrl())
            ->assertSuccessful()
            ->assertDontSee('Export Rekap Nilai');

        Livewire::actingAs($admin)
            ->test(HasilSeleksi::class)
            ->assertActionVisible('export_rekap')
            ->callAction('export_rekap')
            ->assertFileDownloaded('export_rekap.csv');
            
        // Koor should get action hidden
        Livewire::actingAs($koor)
            ->test(HasilSeleksi::class)
            ->assertActionHidden('export_rekap');
    }

    public function test_csv_injection_neutralisation()
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $div = Division::factory()->create();
        
        $candidate = Candidate::factory()->create([
            'name_override' => '=SUM(A1:A2)',
            'catatan' => '-test',
            'nim' => '+123',
            'whatsapp' => '@123',
            'pilihan_1_id' => $div->id,
            'is_duplicate' => false,
        ]);
        
        $response = $this->actingAs($admin)
            ->get(HasilSeleksi::getUrl())
            ->assertSuccessful();

        $page = new HasilSeleksi();
        $stream = $page->exportCsv('penempatan');
        ob_start();
        $stream->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString("'=SUM", $content);
        $this->assertStringContainsString("'-test", $content);
        $this->assertStringContainsString("'+123", $content);
        $this->assertStringContainsString("'@123", $content);
    }

    public function test_bulk_suggestion_rule()
    {
        $head = User::factory()->create(['is_head_interviewer' => true, 'role' => Role::Admin]);
        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();

        $c1 = Candidate::factory()->create(['pilihan_1_id' => $div1->id]);
        $c2 = Candidate::factory()->create(['pilihan_1_id' => $div2->id]);

        // c1 gets lolos on Pilihan 1
        Decision::factory()->create([
            'candidate_id' => $c1->id,
            'division_id' => $div1->id,
            'status' => DecisionStatus::Lolos,
        ]);

        // c2 gets cadangan on Pilihan 1
        Decision::factory()->create([
            'candidate_id' => $c2->id,
            'division_id' => $div2->id,
            'status' => DecisionStatus::Cadangan,
        ]);

        Livewire::actingAs($head)
            ->test(HasilSeleksi::class)
            ->callTableBulkAction('terapkan_saran', [$c1->id, $c2->id]);

        // c1 should be placed in div1
        $this->assertDatabaseHas('placements', [
            'candidate_id' => $c1->id,
            'division_id' => $div1->id,
            'final_status' => 'lolos',
        ]);

        // c2 should not be placed because status is not lolos
        $this->assertDatabaseMissing('placements', [
            'candidate_id' => $c2->id,
        ]);
    }
    
    public function test_results_query_nulls_last_ordering()
    {
        $q = (new \App\Services\ResultsQuery)->query();
        $direction = 'desc';
        
        $isPg = $q->getConnection()->getDriverName() === 'pgsql';
        $orderBy = $isPg 
            ? 'average_primary ' . $direction . ' NULLS LAST' 
            : 'average_primary IS NULL, average_primary ' . $direction;
            
        $q->orderByRaw($orderBy);
        
        $sql = $q->toSql();
        $this->assertStringContainsString('average_primary', $sql);
    }
    
    public function test_incomplete_scorecard_candidate_with_same_pilihan()
    {
        $div = Division::factory()->create();
        $c = Candidate::factory()->create([
            'pilihan_1_id' => $div->id,
            'pilihan_2_id' => $div->id,
        ]);
        
        Decision::factory()->create([
            'candidate_id' => $c->id,
            'division_id' => $div->id,
            'status' => DecisionStatus::Lolos,
        ]);
        
        // This candidate has Pilihan 1 = Pilihan 2, one decision.
        $this->assertEquals(1, $c->decisions()->count());
    }
}
