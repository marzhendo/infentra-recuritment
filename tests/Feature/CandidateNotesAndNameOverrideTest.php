<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CandidateNotesAndNameOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_override_action()
    {
        $user = User::factory()->create();
        $candidate = Candidate::factory()->create(['name' => 'Original', 'name_override' => null]);

        Livewire::actingAs($user)
            ->test(ListCandidates::class)
            ->callTableAction('ubah_nama', $candidate, ['name_override' => 'New Name'])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('New Name', $candidate->fresh()->name_override);
    }

    public function test_catatan_action()
    {
        $user = User::factory()->create();
        $candidate = Candidate::factory()->create(['catatan' => null]);

        Livewire::actingAs($user)
            ->test(ListCandidates::class)
            ->callTableAction('catatan_action', $candidate, ['catatan' => 'Some notes here'])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('Some notes here', $candidate->fresh()->catatan);
    }
}
