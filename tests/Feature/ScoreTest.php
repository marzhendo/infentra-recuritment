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
}
