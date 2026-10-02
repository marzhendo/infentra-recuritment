<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;
use App\Services\ScheduleGenerator;
use Database\Seeders\RubricAspectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_breaks_12_candidates()
    {
        $day = InterviewDay::factory()->create([
            'date' => '2026-10-03',
            'starts_at' => '08:00:00',
            'slot_minutes' => 10,
        ]);

        $candidates = Candidate::factory()->count(12)->create([
            'is_hmif' => false,
        ]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $this->assertEquals(12, InterviewSlot::count());
        $first = InterviewSlot::orderBy('starts_at')->first();
        $this->assertEquals('08:00:00', $first->starts_at);
        $this->assertEquals('08:10:00', $first->ends_at);

        $last = InterviewSlot::orderBy('starts_at', 'desc')->first();
        $this->assertEquals('09:50:00', $last->starts_at);
        $this->assertEquals('10:00:00', $last->ends_at);
    }

    public function test_break_in_middle()
    {
        $day = InterviewDay::factory()->create([
            'starts_at' => '08:00:00',
            'slot_minutes' => 10,
        ]);
        $day->breakBlocks()->create([
            'label' => 'Break',
            'starts_at' => '08:20:00',
            'duration_minutes' => 30, // until 08:50
        ]);

        Candidate::factory()->count(3)->create(['is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $slots = InterviewSlot::orderBy('starts_at')->get();
        $this->assertEquals(3, $slots->count());

        $this->assertEquals('08:00:00', $slots[0]->starts_at);
        $this->assertEquals('08:10:00', $slots[1]->starts_at);
        $this->assertEquals('08:50:00', $slots[2]->starts_at);
    }

    public function test_slot_straddling_break_pushed()
    {
        $day = InterviewDay::factory()->create([
            'starts_at' => '08:00:00',
            'slot_minutes' => 15,
        ]);
        $day->breakBlocks()->create([
            'label' => 'Break',
            'starts_at' => '08:20:00',
            'duration_minutes' => 30, // until 08:50
        ]);

        Candidate::factory()->count(3)->create(['is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $slots = InterviewSlot::orderBy('starts_at')->get();
        // Slot 1: 08:00 - 08:15 (fits)
        // Slot 2: 08:15 - 08:30 (straddles break 08:20). So it jumps to 08:50 - 09:05
        // Slot 3: 09:05 - 09:20
        $this->assertEquals('08:00:00', $slots[0]->starts_at);
        $this->assertEquals('08:50:00', $slots[1]->starts_at);
        $this->assertEquals('09:05:00', $slots[2]->starts_at);
    }

    public function test_hmif_scheduled_but_duplicate_skipped()
    {
        $day = InterviewDay::factory()->create();

        $c_hmif = Candidate::factory()->create(['is_hmif' => true]);
        $c1 = Candidate::factory()->create(['is_hmif' => false]);
        $c2 = Candidate::factory()->create(['is_duplicate' => true, 'is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $this->assertEquals(2, InterviewSlot::count());
        $scheduledIds = InterviewSlot::pluck('candidate_id')->toArray();
        $this->assertContains($c1->id, $scheduledIds);
        $this->assertContains($c_hmif->id, $scheduledIds);
        $this->assertNotContains($c2->id, $scheduledIds);
    }

    public function test_same_name_different_whatsapp_both_scheduled()
    {
        InterviewDay::factory()->create();

        Candidate::factory()->create(['name' => 'Budi', 'whatsapp' => '6281234', 'is_hmif' => false, 'is_duplicate' => false]);
        Candidate::factory()->create(['name' => 'Budi', 'whatsapp' => '6289999', 'is_hmif' => false, 'is_duplicate' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $this->assertEquals(2, InterviewSlot::count());
    }

    public function test_order_by_timestamp()
    {
        $day = InterviewDay::factory()->create();

        $c2 = Candidate::factory()->create(['form_timestamp' => '2026-10-01 10:00:00', 'is_hmif' => false]);
        $c1 = Candidate::factory()->create(['form_timestamp' => '2026-10-01 09:00:00', 'is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $slots = InterviewSlot::orderBy('starts_at')->get();
        $this->assertEquals($c1->id, $slots[0]->candidate_id);
        $this->assertEquals($c2->id, $slots[1]->candidate_id);
    }

    public function test_split_across_two_days()
    {
        InterviewDay::factory()->create(['date' => '2026-10-03']);
        InterviewDay::factory()->create(['date' => '2026-10-04']);

        Candidate::factory()->count(89)->create(['is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        $day1Slots = InterviewSlot::whereHas('interviewDay', fn ($q) => $q->whereDate('date', '2026-10-03'))->count();
        $day2Slots = InterviewSlot::whereHas('interviewDay', fn ($q) => $q->whereDate('date', '2026-10-04'))->count();

        $this->assertEquals(45, $day1Slots);
        $this->assertEquals(44, $day2Slots);
    }

    public function test_idempotent()
    {
        InterviewDay::factory()->create();
        Candidate::factory()->count(5)->create(['is_hmif' => false]);

        $generator = new ScheduleGenerator;
        $generator->generate();
        $count1 = InterviewSlot::count();

        $generator->generate();
        $count2 = InterviewSlot::count();

        $this->assertEquals(5, $count1);
        $this->assertEquals(5, $count2);
    }

    public function test_locked_slots_survive()
    {
        $day = InterviewDay::factory()->create(['starts_at' => '08:00:00', 'slot_minutes' => 10]);
        $c = Candidate::factory()->create(['form_timestamp' => '2026-10-01', 'is_hmif' => false]);
        $c_locked = Candidate::factory()->create(['form_timestamp' => '2026-10-02', 'is_hmif' => false]);

        InterviewSlot::create([
            'interview_day_id' => $day->id,
            'candidate_id' => $c_locked->id,
            'starts_at' => '10:00:00',
            'ends_at' => '10:10:00',
            'is_locked' => true,
        ]);

        $generator = new ScheduleGenerator;
        $generator->generate();

        // c_locked should stay at 10:00. c should get 08:00.
        $this->assertEquals(2, InterviewSlot::count());
        $lockedSlot = InterviewSlot::where('candidate_id', $c_locked->id)->first();
        $this->assertEquals('10:00:00', $lockedSlot->starts_at);
        $this->assertTrue($lockedSlot->is_locked);

        $normalSlot = InterviewSlot::where('candidate_id', $c->id)->first();
        $this->assertEquals('08:00:00', $normalSlot->starts_at);
        $this->assertFalse($normalSlot->is_locked);
    }

    public function test_refuses_when_score_exists()
    {
        $day = InterviewDay::factory()->create();
        $slot = InterviewSlot::create([
            'interview_day_id' => $day->id,
            'starts_at' => '08:00:00',
            'ends_at' => '08:10:00',
        ]);

        $this->seed(RubricAspectSeeder::class);

        DB::table('scores')->insert([
            'slot_id' => $slot->id,
            'interviewer_id' => User::factory()->create()->id,
            'rubric_aspect_id' => 1,
            'value' => 5,
        ]); // dummy score

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot regenerate schedule because scores already exist.');

        $generator = new ScheduleGenerator;
        $generator->generate();
    }
}
