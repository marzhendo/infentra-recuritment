<?php

namespace App\Policies;

use App\Models\InterviewSlot;
use App\Models\Score;
use App\Models\User;

class ScorePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, InterviewSlot $slot): bool
    {
        if ($user->is_head_interviewer) {
            return true;
        }

        if (! $user->division_id) {
            return false;
        }

        $candidate = $slot->candidate;
        if (! $candidate) {
            return false;
        }

        return $user->division_id === $candidate->pilihan_1_id || $user->division_id === $candidate->pilihan_2_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Score $score): bool
    {
        return $user->id === $score->interviewer_id;
    }
}
