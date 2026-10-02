<?php

namespace Tests\Feature;

use App\Models\InterviewSlot;
use App\Models\RubricAspect;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_uniqueness_per_slot_interviewer_aspect(): void
    {
        $slot = InterviewSlot::factory()->create();
        $interviewer = User::factory()->create();
        $aspect = RubricAspect::factory()->create();

        Score::factory()->create([
            'slot_id' => $slot->id,
            'interviewer_id' => $interviewer->id,
            'rubric_aspect_id' => $aspect->id,
            'value' => 5,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Score::factory()->create([
            'slot_id' => $slot->id,
            'interviewer_id' => $interviewer->id,
            'rubric_aspect_id' => $aspect->id,
            'value' => 4,
        ]);
    }

    public function test_candidate_averages()
    {
        $head = User::factory()->create(['is_head_interviewer' => true]);
        
        $div1 = \App\Models\Division::factory()->create();
        $div2 = \App\Models\Division::factory()->create();
        
        $koor1 = User::factory()->create(['role' => \App\Enums\Role::Koor, 'division_id' => $div1->id]);
        $koor2 = User::factory()->create(['role' => \App\Enums\Role::Koor, 'division_id' => $div2->id]);
        $otherPoh = User::factory()->create(['role' => \App\Enums\Role::Admin]);
        
        $candidate = \App\Models\Candidate::factory()->create([
            'pilihan_1_id' => $koor1->division_id,
            'pilihan_2_id' => $koor2->division_id,
        ]);
        
        $slot = InterviewSlot::factory()->create(['candidate_id' => $candidate->id]);
        
        $aspect = clone $candidate; // Just to make sure we don't mess things up
        
        // Null averages initially
        $this->assertNull($candidate->average_primary);
        
        // Head gives 5
        Score::factory()->create(['slot_id' => $slot->id, 'interviewer_id' => $head->id, 'value' => 5]);
        $this->assertEquals(5.0, $candidate->average_primary);
        $this->assertEquals(5.0, $candidate->average_all);
        $this->assertNull($candidate->average_other_poh);
        
        // Koor 1 gives 4
        Score::factory()->create(['slot_id' => $slot->id, 'interviewer_id' => $koor1->id, 'value' => 4]);
        $this->assertEquals(4.5, $candidate->fresh()->average_primary);
        
        // Other POH gives 3
        Score::factory()->create(['slot_id' => $slot->id, 'interviewer_id' => $otherPoh->id, 'value' => 3]);
        $this->assertEquals(3.0, $candidate->fresh()->average_other_poh);
        $this->assertEquals(4.0, $candidate->fresh()->average_all); // (5+4+3)/3 = 4
        $this->assertEquals(4.5, $candidate->fresh()->average_primary); // (5+4)/2 = 4.5
    }
}
