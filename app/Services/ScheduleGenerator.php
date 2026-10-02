<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleGenerator
{
    public function generate(string $order = 'chronological'): array
    {
        // 1. Check if any scores exist
        $hasScores = DB::table('scores')
            ->join('interview_slots', 'scores.slot_id', '=', 'interview_slots.id')
            ->whereNotNull('interview_slots.interview_day_id')
            ->exists();

        if ($hasScores) {
            throw new \Exception('Cannot regenerate schedule because scores already exist.');
        }

        if (InterviewDay::where('is_published', true)->exists()) {
            throw new \Exception('Cannot regenerate schedule because there are published interview days.');
        }

        // 2. Fetch all days and break blocks
        $days = InterviewDay::with('breakBlocks')->orderBy('date')->get();
        if ($days->isEmpty()) {
            return [];
        }

        // 3. Clear ALL UNLOCKED slots for all days
        InterviewSlot::whereNotNull('interview_day_id')
            ->where('is_locked', false)
            ->delete();

        // 4. Fetch eligible candidates (not duplicate)
        $lockedCandidateIds = InterviewSlot::where('is_locked', true)
            ->whereNotNull('candidate_id')
            ->pluck('candidate_id')
            ->toArray();

        $skippedDuplicate = Candidate::where('is_duplicate', true)->count();

        // HMIF candidates are now scheduled!
        $hmifCandidates = Candidate::where('is_hmif', true)
            ->where('is_duplicate', false)
            ->whereNotIn('id', $lockedCandidateIds)
            ->orderBy('form_timestamp')
            ->get();

        $regularQuery = Candidate::where('is_hmif', false)
            ->where('is_duplicate', false)
            ->whereNotIn('id', $lockedCandidateIds);
            
        if ($order === 'random') {
            $regularCandidates = $regularQuery->inRandomOrder()->get();
        } else {
            $regularCandidates = $regularQuery->orderBy('form_timestamp')->get();
        }

        $totalCandidates = $hmifCandidates->count() + $regularCandidates->count();

        // 5. Generate EMPTY slots for each day
        $daysCount = $days->count();
        $basePerDay = floor($totalCandidates / $daysCount);
        $remainder = $totalCandidates % $daysCount;

        $reports = [];
        $newSlots = collect();

        foreach ($days as $dayIndex => $day) {
            $slotsForThisDay = $basePerDay + ($dayIndex < $remainder ? 1 : 0);

            $currentTime = Carbon::parse($day->date->format('Y-m-d').' '.$day->starts_at);
            $slotLength = $day->slot_minutes;
            $breaks = $day->breakBlocks->sortBy('starts_at')->values();

            $sessions = 0;
            $firstStart = null;
            $lastEnd = null;

            $lockedSlots = InterviewSlot::where('interview_day_id', $day->id)
                ->where('is_locked', true)
                ->get()
                ->sortBy('starts_at')
                ->values();

            while ($sessions < $slotsForThisDay) {
                // Check against breaks
                $slotEndTime = (clone $currentTime)->addMinutes($slotLength);

                $jumped = false;
                foreach ($breaks as $break) {
                    $breakStart = Carbon::parse($day->date->format('Y-m-d').' '.$break->starts_at);
                    $breakEnd = (clone $breakStart)->addMinutes($break->duration_minutes);

                    if ($currentTime >= $breakStart && $currentTime < $breakEnd) {
                        $currentTime = clone $breakEnd;
                        $jumped = true;
                        break;
                    }
                    if ($currentTime < $breakStart && $slotEndTime > $breakStart) {
                        $currentTime = clone $breakEnd;
                        $jumped = true;
                        break;
                    }
                }

                if ($jumped) {
                    continue; // Re-evaluate with new currentTime
                }

                // Check against locked slots
                foreach ($lockedSlots as $lSlot) {
                    $lStart = Carbon::parse($day->date->format('Y-m-d').' '.$lSlot->starts_at);
                    $lEnd = Carbon::parse($day->date->format('Y-m-d').' '.$lSlot->ends_at);

                    if ($currentTime >= $lStart && $currentTime < $lEnd) {
                        $currentTime = clone $lEnd;
                        $jumped = true;
                        break;
                    }
                    if ($currentTime < $lStart && $slotEndTime > $lStart) {
                        $currentTime = clone $lEnd;
                        $jumped = true;
                        break;
                    }
                }

                if ($jumped) {
                    continue;
                }

                // Create empty slot
                $slot = InterviewSlot::create([
                    'interview_day_id' => $day->id,
                    'candidate_id' => null,
                    'starts_at' => $currentTime->format('H:i:s'),
                    'ends_at' => $slotEndTime->format('H:i:s'),
                    'is_locked' => false,
                ]);
                $newSlots->push($slot);

                $currentTime = clone $slotEndTime;
                $sessions++;
            }
        }

        // 6. Assign HMIF candidates to slots closest to breaks
        // Calculate distance to nearest break for each new slot
        $slotsWithDistance = $newSlots->map(function ($slot) use ($days) {
            $day = $days->firstWhere('id', $slot->interview_day_id);
            $slotStart = Carbon::parse($day->date->format('Y-m-d').' '.$slot->starts_at);
            $slotEnd = Carbon::parse($day->date->format('Y-m-d').' '.$slot->ends_at);
            
            $minDistance = PHP_INT_MAX;
            foreach ($day->breakBlocks as $break) {
                $breakStart = Carbon::parse($day->date->format('Y-m-d').' '.$break->starts_at);
                $breakEnd = (clone $breakStart)->addMinutes($break->duration_minutes);
                
                $distStart = abs($slotEnd->diffInMinutes($breakStart));
                $distEnd = abs($slotStart->diffInMinutes($breakEnd));
                
                $minDistance = min($minDistance, $distStart, $distEnd);
            }
            
            // If no breaks, just use a large distance
            if ($day->breakBlocks->isEmpty()) {
                $minDistance = PHP_INT_MAX;
            }

            $slot->break_distance = $minDistance;
            return $slot;
        });

        // Sort by distance ascending (closest to break first), then by timestamp
        $sortedForHmif = $slotsWithDistance->sortBy([
            ['break_distance', 'asc'],
            ['starts_at', 'asc']
        ])->values();

        $hmifCount = $hmifCandidates->count();
        $hmifSlots = $sortedForHmif->take($hmifCount);
        
        // The rest of the slots
        $regularSlots = $sortedForHmif->slice($hmifCount)->sortBy([
            ['interview_day_id', 'asc'],
            ['starts_at', 'asc']
        ])->values();

        // Assign HMIF
        foreach ($hmifCandidates as $index => $candidate) {
            $slot = $hmifSlots[$index];
            unset($slot->break_distance);
            $slot->update(['candidate_id' => $candidate->id]);
        }

        // Assign Regular
        foreach ($regularCandidates as $index => $candidate) {
            $slot = $regularSlots[$index];
            unset($slot->break_distance);
            $slot->update(['candidate_id' => $candidate->id]);
        }

        // 7. Update Day estimates
        foreach ($days as $day) {
            $trueFirstStart = InterviewSlot::where('interview_day_id', $day->id)->min('starts_at');
            $trueLastEnd = InterviewSlot::where('interview_day_id', $day->id)->max('ends_at');
            $day->update(['ends_at' => $trueLastEnd]);
            $totalSessions = InterviewSlot::where('interview_day_id', $day->id)->count();

            $reports[$day->date->format('Y-m-d')] = [
                'sessions' => $totalSessions,
                'first_start' => $trueFirstStart ? substr((string)$trueFirstStart, 0, 5) : '-',
                'estimated_finish' => $trueLastEnd ? substr((string)$trueLastEnd, 0, 5) : '-',
                'left_over' => 0,
            ];
        }

        return [
            'days' => $reports,
            'skipped_hmif' => 0,
            'skipped_duplicate' => $skippedDuplicate,
        ];
    }
}
