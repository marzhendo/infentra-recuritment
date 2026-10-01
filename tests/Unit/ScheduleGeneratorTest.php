<?php

namespace Tests\Unit;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Services\ScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_generator_avoids_locked_slots()
    {
        $day = InterviewDay::factory()->create([
            'starts_at' => '08:00:00',
            'slot_minutes' => 10,
        ]);

        $candidates = Candidate::factory()->count(2)->create(['is_hmif' => false, 'is_duplicate' => false]);
        
        // Lock a slot exactly at 08:00 for a third candidate
        $lockedCandidate = Candidate::factory()->create(['is_hmif' => false, 'is_duplicate' => false]);
        $lockedSlot = InterviewSlot::create([
            'interview_day_id' => $day->id,
            'candidate_id' => $lockedCandidate->id,
            'starts_at' => '08:00:00',
            'ends_at' => '08:10:00',
            'is_locked' => true,
        ]);

        $generator = new ScheduleGenerator();
        $report = $generator->generate();

        $slots = InterviewSlot::orderBy('starts_at')->get();
        
        $this->assertCount(3, $slots);
        
        // 1. The locked slot should remain at 08:00
        $this->assertEquals('08:00:00', $slots[0]->starts_at);
        $this->assertTrue((bool)$slots[0]->is_locked);
        $this->assertEquals($lockedCandidate->id, $slots[0]->candidate_id);

        // 2. The first non-locked candidate should be assigned to 08:10
        $this->assertEquals('08:10:00', $slots[1]->starts_at);
        $this->assertEquals('08:20:00', $slots[1]->ends_at);
        
        // 3. The second non-locked candidate should be assigned to 08:20
        $this->assertEquals('08:20:00', $slots[2]->starts_at);
        $this->assertEquals('08:30:00', $slots[2]->ends_at);
        
        // Verify Day ends_at is correctly reported
        $day->refresh();
        $this->assertEquals('08:30:00', $day->ends_at);
    }
}
