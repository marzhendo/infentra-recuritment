<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Division;
use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

class POHFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_resources()
    {
        $admin = User::factory()->create(['role' => 'admin', 'jabatan' => 'Ketua Pelaksana']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Calon Panitia')
            ->assertSee('Jadwal')
            ->assertSee('Wawancara');

        $this->actingAs($admin)
            ->get('/admin/candidates')
            ->assertOk()
            ->assertSee('Impor data dari CSV');
            
        $this->actingAs($admin)
            ->get('/admin/interview-days')
            ->assertOk();
    }

    public function test_koor_cannot_see_jadwal_and_import()
    {
        $division = Division::factory()->create();
        $koor = User::factory()->create(['role' => 'koor', 'jabatan' => 'Koor Acara', 'division_id' => $division->id]);

        $this->actingAs($koor)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Calon Panitia')
            ->assertSee('Wawancara')
            ->assertDontSee('href="http://localhost/admin/interview-days"', false);

        $this->actingAs($koor)
            ->get('/admin/candidates')
            ->assertOk()
            ->assertDontSee('Impor data dari CSV');

        $this->actingAs($koor)
            ->get('/admin/interview-days')
            ->assertForbidden();
    }

    public function test_poh_flow_pages_render()
    {
        $division = Division::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'jabatan' => 'Ketua Pelaksana']);
        
        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $division->id,
            'pilihan_2_id' => $division->id,
        ]);
        
        $day = InterviewDay::factory()->create(['date' => '2026-10-03']);
        $slot = InterviewSlot::factory()->create(['interview_day_id' => $day->id, 'candidate_id' => $candidate->id]);

        $this->actingAs($admin);

        // Dashboard
        $this->get('/admin')->assertOk()->assertSee('Impor data calon');
        
        // Candidates
        $this->get('/admin/candidates')->assertOk()->assertSee('Calon Panitia');
        $this->get('/admin/candidates/' . $candidate->id)->assertOk();
        
        // Interview Days
        $this->get('/admin/interview-days')->assertOk();
        
        // Interview Slots
        $this->get('/admin/interview-slots')->assertOk();
    }
}
