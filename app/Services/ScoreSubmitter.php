<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateNote;
use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScoreSubmitter
{
    public function submit(InterviewSlot $slot, User $interviewer, array $scores, ?string $note = null): void
    {
        $activeAspects = DB::table('rubric_aspects')->where('is_active', true)->pluck('id')->toArray();

        // 1. Submit Scores
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
                ]
            );
        }

        // 2. Submit Note (if candidate exists and there's a note)
        if ($slot->candidate_id && $note !== null) {
            $this->submitNote($slot->candidate, $interviewer, $note);
        }
    }
    
    public function submitNote(Candidate $candidate, User $author, ?string $note): void
    {
        if ($note === null || trim($note) === '') {
            // Note could be deleted? The prompt says one note per author, maybe they clear it.
            // But let's just create/update if not empty, or delete if empty.
            if ($note !== null && trim($note) === '') {
                CandidateNote::where('candidate_id', $candidate->id)
                    ->where('author_id', $author->id)
                    ->delete();
            }
            return;
        }
        
        CandidateNote::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'author_id' => $author->id,
            ],
            [
                'body' => $note,
            ]
        );
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
