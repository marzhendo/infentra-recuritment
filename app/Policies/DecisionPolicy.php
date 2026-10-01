<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\Division;
use App\Models\User;

class DecisionPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Candidate $candidate, Division $division): bool
    {
        if (! $user->division_id) {
            return false;
        }

        // Koor may set decision ONLY for their own division
        if ($user->division_id !== $division->id) {
            return false;
        }

        // and only if that division is candidate's Pilihan 1 or Pilihan 2
        return $division->id === $candidate->pilihan_1_id || $division->id === $candidate->pilihan_2_id;
    }
}
