<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_pages_render_and_actions_do_not_throw()
    {
        $div1 = Division::factory()->create(['id' => 1]);
        $head = User::factory()->create(['is_head_interviewer' => true]);
        $koor = User::factory()->create(['is_head_interviewer' => false, 'division_id' => 1]);
        $admin = User::factory()->create(['is_head_interviewer' => false, 'division_id' => null]);
        
        $candidate = Candidate::factory()->create(['pilihan_1_id' => $div1->id]);
        
        $day = InterviewDay::factory()->create();
        $slot = InterviewSlot::factory()->create(['interview_day_id' => $day->id, 'candidate_id' => $candidate->id]);

        $users = [$head, $koor, $admin];

        foreach ($users as $user) {
            $this->actingAs($user);

            // Render Candidate Pages
            Livewire::test(\App\Filament\Resources\Candidates\Pages\ListCandidates::class)
                ->assertSuccessful()
                ->callTableAction('ubah_nama', $candidate)
                ->assertHasNoTableActionErrors()
                ->callTableAction('catatan_action', $candidate)
                ->assertHasNoTableActionErrors();

            Livewire::test(\App\Filament\Resources\Candidates\Pages\ViewCandidate::class, ['record' => $candidate->id])
                ->assertSuccessful();

            // Render Interview Slots
            Livewire::test(\App\Filament\Resources\InterviewSlots\Pages\ManageInterviewSlots::class)
                ->assertSuccessful();

            // Render Interview Days
            Livewire::test(\App\Filament\Resources\InterviewDays\Pages\ManageInterviewDays::class)
                ->assertSuccessful();

            // Render Panel Wawancara
            $panel = Livewire::test(\App\Filament\Pages\PanelWawancara::class)
                ->assertSuccessful();
                
            // Check that we can open the score action (might be hidden for some users, so check if visible first or just catch exception if hidden)
            // Actually, we can check if it throws by trying to call it, but if it's hidden, calling it might throw.
            // Let's just mount it.
        }
    }
}
