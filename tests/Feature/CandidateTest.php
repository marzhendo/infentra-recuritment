<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_belongs_to_two_divisions(): void
    {
        $div1 = Division::factory()->create(['name' => 'Div 1']);
        $div2 = Division::factory()->create(['name' => 'Div 2']);

        $candidate = Candidate::factory()->create([
            'pilihan_1_id' => $div1->id,
            'pilihan_2_id' => $div2->id,
        ]);

        $this->assertEquals('Div 1', $candidate->pilihan1->name);
        $this->assertEquals('Div 2', $candidate->pilihan2->name);
    }
}
