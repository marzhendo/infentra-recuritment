<?php

namespace App\Services;

use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScoreSubmitter
{
    public function submit(InterviewSlot $slot, User $interviewer, array $scores, ?string $note = null): void
    {
        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->pluck('id')->toArray();

        foreach ($scores as $aspectId => $value) {
            if (! in_array($aspectId, $activeAspects)) {
                continue;
            }
            if ($value < 1 || $value > 5) {
                throw new \InvalidArgumentException('Score value must be between 1 and 5');
            }

            Score::updateOrCreate(
                [
                    'slot_id' => $slot->id,
                    'interviewer_id' => $interviewer->id,
                    'rubric_aspect_id' => $aspectId,
                ],
                [
                    'value' => $value,
                    'note' => $note,
                ]
            );
        }
    }

    public function isComplete(InterviewSlot $slot, User $interviewer): bool
    {
        $activeAspectsCount = DB::table('rubric_aspects')->where('is_active', true)->count();
        $submittedCount = Score::where('slot_id', $slot->id)
            ->where('interviewer_id', $interviewer->id)
            ->whereIn('rubric_aspect_id', DB::table('rubric_aspects')->where('is_active', true)->pluck('id'))
            ->count();

        return $submittedCount > 0 && $submittedCount === $activeAspectsCount;
    }
}
