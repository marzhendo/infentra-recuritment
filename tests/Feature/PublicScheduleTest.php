<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
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

    public function test_less_than_3_chars_returns_no_results()
    {
        Candidate::factory()->create(['name' => 'John Doe']);

        Livewire::test(\App\Livewire\PublicSchedule::class)
            ->set('search', 'Jo')
            ->assertSet('results', []);
    }

    public function test_search_matches_display_name_and_shows_no_sensitive_data()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'JOHN DOE',
            'nim' => '12345678',
            'whatsapp' => '08123456789',
        ]);
        
        $day = InterviewDay::factory()->create(['date' => '2026-10-03']);
        $slot = InterviewSlot::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_day_id' => $day->id,
            'starts_at' => '10:00:00',
            'ends_at' => '10:10:00',
        ]);

        $component = Livewire::test(\App\Livewire\PublicSchedule::class)
            ->set('search', 'john')
            ->assertSee('John Doe')
            ->assertSee('03 Oct 2026')
            ->assertSee('10:00 - 10:10')
            ->assertSee('Ruang Wawancara DC-302')
            ->assertDontSee('12345678')
            ->assertDontSee('08123456789');
    }

    public function test_hmif_exemption()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'HMIF Student',
            'is_hmif' => true,
        ]);

        Livewire::test(\App\Livewire\PublicSchedule::class)
            ->set('search', 'hmi')
            ->assertSee('HMIF Student')
            ->assertSee('Dibebaskan dari wawancara')
            ->assertDontSee('Ruang Wawancara');
    }

    public function test_no_slot()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Pending Student',
        ]);

        Livewire::test(\App\Livewire\PublicSchedule::class)
            ->set('search', 'pendi')
            ->assertSee('Pending Student')
            ->assertSee('Belum dijadwalkan');
    }

    public function test_rate_limit()
    {
        $key = 'public-schedule:127.0.0.1';
        RateLimiter::clear($key);

        $component = Livewire::test(\App\Livewire\PublicSchedule::class);
        
        for ($i = 0; $i < 30; $i++) {
            $component->set('search', 'test' . $i);
        }

        $component->set('search', 'test31')
            ->assertHasErrors(['search']);
            
        $this->assertStringContainsString('Terlalu banyak permintaan', $component->errors()->first('search'));
    }
}
