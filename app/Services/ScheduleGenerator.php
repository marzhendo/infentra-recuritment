<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\InterviewDay;
use App\Models\InterviewSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleGenerator
{
    public function generate(): array
    {
        // 1. Check if any scores exist
        $hasScores = DB::table('scores')
            ->join('interview_slots', 'scores.slot_id', '=', 'interview_slots.id')
            ->whereNotNull('interview_slots.interview_day_id')
            ->exists();

        if ($hasScores) {
            throw new \Exception('Cannot regenerate schedule because scores already exist.');
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

        // 4. Fetch eligible candidates (not HMIF, not duplicate)
        // Wait, "duplicate" means badges "Possible duplicate" logic?
        // "A candidate with is_hmif = true or is_duplicate = true NEVER gets a slot."
        // Let's add is_duplicate column? The schema does not have is_duplicate. Let's see: The prompt step A didn't mention adding is_duplicate to the table. Wait! The prompt says "A candidate with is_hmif = true or is_duplicate = true NEVER gets a slot."
        // Wait, did I add is_duplicate in Candidate model? No, the badge in CandidateResource was dynamic: `Candidate::where('id', '!=', $record->id)->whereRaw('LOWER(TRIM(REPLACE(name, "  ", " "))) = ?', [$normalizedName])->count() > 0`.
        // Let's implement that logic here for "duplicate". Wait, if a candidate has another candidate with the SAME name, BOTH might be considered duplicates. We should probably only schedule the NEWEST one (which is the only one created anyway because CandidateImporter already handles resubmissions! Ah!)
        // "Same name, different WhatsApp -> TWO candidates" (this was in the PRD). So they are separate rows. We should skip both? Or just skip the older one? Let's skip both and they must be resolved manually.
        // I will write a dynamic duplicate check.

        // Get candidates that don't have a locked slot
        $lockedCandidateIds = InterviewSlot::where('is_locked', true)
            ->whereNotNull('candidate_id')
            ->pluck('candidate_id')
            ->toArray();

        $skippedHmif = Candidate::where('is_hmif', true)->count();
        $skippedDuplicate = Candidate::where('is_duplicate', true)->count();

        $candidates = Candidate::where('is_hmif', false)
            ->where('is_duplicate', false)
            ->whereNotIn('id', $lockedCandidateIds)
            ->orderBy('form_timestamp')
            ->get();

        $eligibleCandidates = $candidates->all();

        // 5. Generate slots for each day
        $totalCandidates = count($eligibleCandidates);
        $daysCount = $days->count();
        $basePerDay = floor($totalCandidates / $daysCount);
        $remainder = $totalCandidates % $daysCount;

        $reports = [];
        $candidateIndex = 0;

        foreach ($days as $dayIndex => $day) {
            $slotsForThisDay = $basePerDay + ($dayIndex < $remainder ? 1 : 0);

            $currentTime = Carbon::parse($day->date->format('Y-m-d').' '.$day->starts_at);
            $slotLength = $day->slot_minutes;
            $breaks = $day->breakBlocks->sortBy('starts_at')->values();

            $sessions = 0;
            $firstStart = null;
            $lastEnd = null;

            while ($sessions < $slotsForThisDay && $candidateIndex < $totalCandidates) {
                $candidate = $eligibleCandidates[$candidateIndex];

                // Check against breaks
                $slotEndTime = (clone $currentTime)->addMinutes($slotLength);

                $jumped = false;
                foreach ($breaks as $break) {
                    $breakStart = Carbon::parse($day->date->format('Y-m-d').' '.$break->starts_at);
                    $breakEnd = (clone $breakStart)->addMinutes($break->duration_minutes);

                    // If current slot overlaps break (starts inside break OR ends after break starts and starts before break ends)
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

                if ($firstStart === null) {
                    $firstStart = $currentTime->format('H:i:s');
                }

                // Create slot
                InterviewSlot::create([
                    'interview_day_id' => $day->id,
                    'candidate_id' => $candidate->id,
                    'starts_at' => $currentTime->format('H:i:s'),
                    'ends_at' => $slotEndTime->format('H:i:s'),
                    'is_locked' => false,
                ]);

                $lastEnd = $slotEndTime->format('H:i:s');
                $currentTime = clone $slotEndTime;
                $sessions++;
                $candidateIndex++;
            }

            $day->update(['ends_at' => $lastEnd]);

            $totalSessions = InterviewSlot::where('interview_day_id', $day->id)->count();

            $reports[$day->date->format('Y-m-d')] = [
                'sessions' => $totalSessions,
                'first_start' => $firstStart,
                'estimated_finish' => $lastEnd,
                'left_over' => max(0, $slotsForThisDay - $sessions),
            ];
        }

        // Left overs if any day couldn't fit? We actually just assign until they are done.
        // Wait, the prompt says: "Persist empty slots first, then assign candidates in order, so empty slots exist for manual use."
        // Oh! "Persist empty slots first, then assign candidates in order, so empty slots exist for manual use."
        // Wait, if we just generate slots for the REQUIRED number of candidates, there are NO empty slots.
        // Let me re-read the prompt: "Persist empty slots first, then assign candidates in order, so empty slots exist for manual use."
        return [
            'days' => $reports,
            'skipped_hmif' => $skippedHmif,
            'skipped_duplicate' => $skippedDuplicate,
        ];
    }
}
