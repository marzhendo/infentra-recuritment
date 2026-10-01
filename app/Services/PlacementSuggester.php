<?php

namespace App\Services;

use App\Enums\DecisionStatus;
use App\Models\Candidate;
use App\Models\Decision;
use App\Models\Division;

class PlacementSuggester
{
    public function suggest(Candidate $candidate): ?Division
    {
        $decisions = Decision::where('candidate_id', $candidate->id)->get()->keyBy('division_id');

        if ($candidate->pilihan_1_id) {
            $dec1 = $decisions->get($candidate->pilihan_1_id);
            if ($dec1 && $dec1->status === DecisionStatus::Lolos) {
                return $candidate->pilihan1;
            }
        }

        // As per prompt: "If the Pilihan 1 decision is lolos, suggest Pilihan 1. Otherwise suggest nothing"
        return null;
    }
}
