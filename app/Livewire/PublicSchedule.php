<?php

namespace App\Livewire;

use App\Models\Candidate;
use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublicSchedule extends Component
{
    #[Url]
    public $search = '';

    public function updatedSearch()
    {
        $this->validateRateLimit();
    }

    protected function validateRateLimit()
    {
        $key = 'public-schedule:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'search' => "Terlalu banyak permintaan. Silakan coba lagi dalam $seconds detik."
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    public function getResultsProperty()
    {
        if (strlen($this->search) < 3) {
            return [];
        }

        return Candidate::query()
            ->with(['slot.interviewDay'])
            ->whereRaw('lower(name) like ?', ['%' . strtolower($this->search) . '%'])
            ->orWhereRaw('lower(name_override) like ?', ['%' . strtolower($this->search) . '%'])
            ->limit(10)
            ->get()
            ->map(function ($candidate) {
                if ($candidate->is_hmif) {
                    $status = 'Dibebaskan dari wawancara';
                    $room = '-';
                    $time = '-';
                } elseif ($candidate->slot) {
                    $slot = $candidate->slot;
                    $status = \Carbon\Carbon::parse($slot->interviewDay->date)->format('d M Y');
                    $time = substr($slot->starts_at, 0, 5) . ' - ' . substr($slot->ends_at, 0, 5);
                    $room = 'Ruang Wawancara DC-302';
                } else {
                    $status = 'Belum dijadwalkan';
                    $room = '-';
                    $time = '-';
                }

                return [
                    'name' => $candidate->display_name,
                    'status' => $status,
                    'time' => $time,
                    'room' => $room,
                ];
            });
    }

    public function render()
    {
        return view('livewire.public-schedule')->layout('layouts.app');
    }
}
