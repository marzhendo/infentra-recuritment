<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Candidate;
use App\Models\CandidateNote;
use App\Models\Division;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;
use App\Policies\NotePolicy;
use App\Policies\ScorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreAndNotePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_policy_permissions()
    {
        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();
        $div3 = Division::factory()->create();

        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'pilihan_2_id' => $div2->id,
            'is_hmif' => false,
        ]);
        $slot = InterviewSlot::factory()->create(['candidate_id' => $candidate->id]);
        
        $hmifCandidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'is_hmif' => true,
        ]);
        $hmifSlot = InterviewSlot::factory()->create(['candidate_id' => $hmifCandidate->id]);

        $head = User::factory()->create(['is_head_interviewer' => true, 'role' => Role::Admin]);
        $admin = User::factory()->create(['is_head_interviewer' => false, 'role' => Role::Admin]);
        $koor1 = User::factory()->create(['division_id' => $div1->id, 'role' => Role::Koor]);
        $koor2 = User::factory()->create(['division_id' => $div2->id, 'role' => Role::Koor]);
        $koor3 = User::factory()->create(['division_id' => $div3->id, 'role' => Role::Koor]);

        $policy = new ScorePolicy;

        // Head and admin can score normal
        $this->assertTrue($policy->create($head, $slot));
        $this->assertTrue($policy->create($admin, $slot));
        // Koor 1 and 2 can score this candidate
        $this->assertTrue($policy->create($koor1, $slot));
        $this->assertTrue($policy->create($koor2, $slot));
        // Koor 3 cannot
        $this->assertFalse($policy->create($koor3, $slot));
        
        // NO ONE can score HMIF
        $this->assertFalse($policy->create($head, $hmifSlot));
        $this->assertFalse($policy->create($koor1, $hmifSlot));
        
        // Without slot, there is no ScorePolicy call possible for creating (requires InterviewSlot)
        
        // Can only update own score
        $score = Score::factory()->create(['slot_id' => $slot->id, 'interviewer_id' => $head->id]);
        $this->assertTrue($policy->update($head, $score));
        $this->assertFalse($policy->update($admin, $score));
        $this->assertFalse($policy->update($koor1, $score));
    }

    public function test_note_policy_permissions()
    {
        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();
        $div3 = Division::factory()->create();

        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'pilihan_2_id' => $div2->id,
            'is_hmif' => false,
        ]);
        
        $hmifCandidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'is_hmif' => true,
        ]);

        $head = User::factory()->create(['is_head_interviewer' => true, 'role' => Role::Admin]);
        $admin = User::factory()->create(['is_head_interviewer' => false, 'role' => Role::Admin]);
        $koor1 = User::factory()->create(['division_id' => $div1->id, 'role' => Role::Koor]);
        $koor2 = User::factory()->create(['division_id' => $div2->id, 'role' => Role::Koor]);
        $koor3 = User::factory()->create(['division_id' => $div3->id, 'role' => Role::Koor]);

        $policy = new NotePolicy;

        // Head and admin can note normal and HMIF
        $this->assertTrue($policy->create($head, $candidate));
        $this->assertTrue($policy->create($admin, $candidate));
        $this->assertTrue($policy->create($head, $hmifCandidate));
        
        // Koor 1 and 2 can note this candidate
        $this->assertTrue($policy->create($koor1, $candidate));
        $this->assertTrue($policy->create($koor2, $candidate));
        // Koor 1 can also note HMIF if chosen their division
        $this->assertTrue($policy->create($koor1, $hmifCandidate));
        
        // Koor 3 cannot note
        $this->assertFalse($policy->create($koor3, $candidate));
        
        // Note: candidate without slot can be noted as we pass Candidate model
        $noSlotCandidate = Candidate::factory()->create(['pilihan_1_id' => $div1->id]);
        $this->assertTrue($policy->create($head, $noSlotCandidate));
        
        // Can only update own note
        $note = CandidateNote::create(['candidate_id' => $candidate->id, 'author_id' => $head->id, 'body' => 'Test']);
        $this->assertTrue($policy->update($head, $note));
        $this->assertFalse($policy->update($admin, $note));
    }
}
