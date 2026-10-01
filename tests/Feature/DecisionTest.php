<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_decision_uniqueness_per_candidate_division(): void
    {
        $candidate = Candidate::factory()->create();
        $division = Division::factory()->create();

        Decision::factory()->create([
            'candidate_id' => $candidate->id,
            'division_id' => $division->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Decision::factory()->create([
            'candidate_id' => $candidate->id,
            'division_id' => $division->id,
        ]);
    }
}
