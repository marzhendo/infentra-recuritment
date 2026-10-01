<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\User;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CandidateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_tandai_hmif()
    {
        $user = User::factory()->create();
        
        $candidates = Candidate::factory()->count(3)->create(['is_hmif' => false]);
        
        Livewire::actingAs($user)
            ->test(ListCandidates::class)
            ->callTableBulkAction('tandai_hmif_bulk', $candidates);
            
        foreach ($candidates as $c) {
            $this->assertTrue($c->fresh()->is_hmif);
        }
    }
}
