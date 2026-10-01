<?php

namespace Tests\Feature;

use App\Enums\DecisionStatus;
use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Division;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;
use App\Policies\DecisionPolicy;
use App\Policies\ScorePolicy;
use App\Services\PlacementSuggester;
use App\Services\ScoreSubmitter;
use Database\Seeders\RubricAspectSeeder;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreAndDecisionPolicyTest extends TestCase
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

        $head = User::factory()->create(['is_head_interviewer' => true]);
        $koor1 = User::factory()->create(['division_id' => $div1->id]);
        $koor2 = User::factory()->create(['division_id' => $div2->id]);
        $koor3 = User::factory()->create(['division_id' => $div3->id]);
        $admin = User::factory()->create(['is_head_interviewer' => false]);

        $policy = new ScorePolicy;

        // Head can score anyone
        $this->assertTrue($policy->create($head, $slot));
        // Koor 1 and 2 can score this candidate
        $this->assertTrue($policy->create($koor1, $slot));
        $this->assertTrue($policy->create($koor2, $slot));
        // Koor 3 and admin cannot
        $this->assertFalse($policy->create($koor3, $slot));
        $this->assertFalse($policy->create($admin, $slot));

        // Can only update own score
        $score = Score::factory()->create(['slot_id' => $slot->id, 'interviewer_id' => $head->id]);
        $this->assertTrue($policy->update($head, $score));
        $this->assertFalse($policy->update($koor1, $score));
    }

    public function test_decision_policy_permissions()
    {
        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();
        $div3 = Division::factory()->create();

        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'pilihan_2_id' => $div2->id,
            'is_hmif' => false,
        ]);

        $koor1 = User::factory()->create(['division_id' => $div1->id]);
        $koor2 = User::factory()->create(['division_id' => $div2->id]);
        $koor3 = User::factory()->create(['division_id' => $div3->id]);

        $policy = new DecisionPolicy;

        // Koor can create decision for their division on this candidate
        $this->assertTrue($policy->create($koor1, $candidate, $div1));
        // Koor cannot create decision for a different division
        $this->assertFalse($policy->create($koor1, $candidate, $div2));

        // Unrelated koor cannot create decision
        $this->assertFalse($policy->create($koor3, $candidate, $div3)); // candidate didn't pick div3

        // Same for HMIF candidate
        $hmifCandidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'is_hmif' => true,
        ]);
        $this->assertTrue($policy->create($koor1, $hmifCandidate, $div1));
    }

    public function test_score_submitter_validates_values_and_completeness()
    {
        $this->seed(RubricAspectSeeder::class);
        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->get();
        $this->assertGreaterThan(0, $activeAspects->count());

        $slot = InterviewSlot::factory()->create();
        $user = User::factory()->create();

        $submitter = new ScoreSubmitter;

        // Values outside 1-5 rejected
        $this->expectException(\InvalidArgumentException::class);
        $submitter->submit($slot, $user, [$activeAspects->first()->id => 6]);
    }

    public function test_score_submitter_partial_and_completeness()
    {
        $this->seed(RubricAspectSeeder::class);
        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->get();

        $slot = InterviewSlot::factory()->create();
        $user = User::factory()->create();

        $submitter = new ScoreSubmitter;

        // Partial save
        $firstAspectId = $activeAspects->first()->id;
        $submitter->submit($slot, $user, [$firstAspectId => 4]);

        $this->assertFalse($submitter->isComplete($slot, $user));

        // Complete save
        $allScores = [];
        foreach ($activeAspects as $a) {
            $allScores[$a->id] = 5;
        }
        $submitter->submit($slot, $user, $allScores);

        $this->assertTrue($submitter->isComplete($slot, $user));
    }

    public function test_placement_suggester()
    {
        $div1 = Division::factory()->create();
        $div2 = Division::factory()->create();

        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'pilihan_2_id' => $div2->id,
        ]);

        $suggester = new PlacementSuggester;

        // No decisions -> no suggestion
        $this->assertNull($suggester->suggest($candidate));

        // Pil 1 lolos -> Pil 1
        Decision::create([
            'candidate_id' => $candidate->id,
            'division_id' => $div1->id,
            'status' => DecisionStatus::Lolos,
        ]);
        $this->assertEquals($div1->id, $suggester->suggest($candidate)?->id);

        // Pil 1 tidak lolos, Pil 2 lolos -> None (prompt says: If Pilihan 1 lolos, suggest Pilihan 1. Otherwise suggest nothing)
        Decision::where('division_id', $div1->id)->update(['status' => DecisionStatus::TidakLolos]);
        Decision::create([
            'candidate_id' => $candidate->id,
            'division_id' => $div2->id,
            'status' => DecisionStatus::Lolos,
        ]);

        $this->assertNull($suggester->suggest($candidate));
    }
}
