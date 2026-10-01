<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\InterviewSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_belongs_to_candidate(): void
    {
        $candidate = Candidate::factory()->create();
        $slot = InterviewSlot::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $this->assertEquals($candidate->id, $slot->candidate->id);
    }
    
    public function test_candidate_can_only_have_one_slot(): void
    {
        $candidate = Candidate::factory()->create();
        InterviewSlot::factory()->create([
            'candidate_id' => $candidate->id,
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        InterviewSlot::factory()->create([
            'candidate_id' => $candidate->id,
        ]);
    }
}
