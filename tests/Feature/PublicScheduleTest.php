<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use App\Models\BreakBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_and_noindex_header()
    {
        $response = $this->get('/jadwal');
        $response->assertSuccessful();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_public_schedule_logic()
    {
        $day1 = InterviewDay::factory()->create(['date' => '2026-10-03', 'is_published' => true]);
        $day2 = InterviewDay::factory()->create(['date' => '2026-10-04', 'is_published' => false]);

        $hmifCandidate = Candidate::factory()->create(['name' => 'HMIF Student', 'is_hmif' => true]);
        $normalCandidate = Candidate::factory()->create(['name' => 'John Doe', 'nim' => '12345678']);
        $hiddenCandidate = Candidate::factory()->create(['name' => 'Hidden Jane']);

        $slot1 = InterviewSlot::factory()->create([
            'candidate_id' => $normalCandidate->id,
            'interview_day_id' => $day1->id,
            'starts_at' => '10:00:00',
            'ends_at' => '10:10:00',
        ]);
        $slot2 = InterviewSlot::factory()->create([
            'candidate_id' => $hiddenCandidate->id,
            'interview_day_id' => $day2->id,
            'starts_at' => '11:00:00',
            'ends_at' => '11:10:00',
        ]);

        $break = BreakBlock::factory()->create([
            'interview_day_id' => $day1->id,
            'label' => 'Istirahat Dzuhur',
            'starts_at' => '11:45:00',
            'duration_minutes' => 60,
        ]);

        $component = Livewire::test(\App\Livewire\PublicSchedule::class)
            ->assertSee('John Doe')
            ->assertSee('10:00')
            ->assertSee('Istirahat Dzuhur')
            ->assertDontSee('Hidden Jane') // Day 2 is unpublished
            ->assertDontSee('HMIF Student') // HMIF absent
            
            ->assertDontSee('12345678'); // Privacy

        // Verify JSON response or HTML doesn't leak Candidate ID or NIM
        $html = $component->html();
        $this->assertStringNotContainsString('12345678', $html);
    }

    public function test_empty_when_nothing_published()
    {
        Livewire::test(\App\Livewire\PublicSchedule::class)
            ->assertSee('Jadwal belum dipublikasikan');
    }
}
