<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\CandidateNote;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Candidate $candidate): bool
    {
        if ($user->is_head_interviewer || $user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($user->division_id && in_array($user->division_id, [$candidate->pilihan_1_id, $candidate->pilihan_2_id])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CandidateNote $note): bool
    {
        return $user->id === $note->author_id;
    }
}
