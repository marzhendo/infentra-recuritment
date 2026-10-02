<?php

namespace App\Livewire;

use App\Models\InterviewDay;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicSchedule extends Component
{
    public function mount()
    {
        $key = 'public-schedule:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            $seconds = RateLimiter::availableIn($key);
            abort(429, "Terlalu banyak permintaan. Silakan coba lagi dalam $seconds detik.");
        }

        RateLimiter::hit($key, 60);
    }

    public function getDaysProperty()
    {
        return Cache::remember('public_schedule_days', 60, function () {
            return InterviewDay::with(['interviewSlots.candidate', 'breakBlocks'])
                ->where('is_published', true)
                ->orderBy('date')
                ->get()
                ->map(function ($day) {
                    $items = collect();

                    foreach ($day->interviewSlots as $slot) {
                        if ($slot->candidate) {
                            $items->push([
                                'type' => 'slot',
                                'starts_at' => substr($slot->starts_at, 0, 5),
                                'ends_at' => substr($slot->ends_at, 0, 5),
                                'sort_time' => $slot->starts_at,
                                'display_name' => $slot->candidate->display_name,
                            ]);
                        }
                    }

                    foreach ($day->breakBlocks as $break) {
                        $items->push([
                            'type' => 'break',
                            'starts_at' => substr($break->starts_at, 0, 5),
                            'ends_at' => substr($break->ends_at, 0, 5),
                            'sort_time' => $break->starts_at,
                            'title' => $break->label,
                        ]);
                    }

                    return [
                        'id' => $day->id,
                        'date' => \Carbon\Carbon::parse($day->date)->translatedFormat('l, d F Y'),
                        'raw_date' => (string) $day->date, // Cast to string to prevent Carbon serialization issues
                        'items' => $items->sortBy('sort_time')->values()->all(),
                    ];
                })->toArray();
        });
    }

    public function render()
    {
        return view('livewire.public-schedule', [
            'days' => $this->days,
            'lastUpdated' => now()->timezone('Asia/Jakarta')->translatedFormat('H:i'),
        ])->layout('layouts.app');
    }
}
