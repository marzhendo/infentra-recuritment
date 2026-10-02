<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ResultsQuery
{
    public function query(): Builder
    {
        $headId = User::where('is_head_interviewer', true)->value('id') ?? -1;

        $q = Candidate::query()
            ->with(['pilihan1', 'pilihan2', 'decisions', 'placement', 'placement.division', 'slot', 'notes', 'notes.author'])
            ->where('is_duplicate', false)
            ->select('candidates.*');

        // Subquery for slot_id
        $slotIdQuery = DB::table('interview_slots')
            ->select('id')
            ->whereColumn('candidate_id', 'candidates.id')
            ->limit(1);

        // Pilihan 1 koor ID subquery
        $p1KoorQuery = DB::table('users')
            ->select('id')
            ->whereColumn('division_id', 'candidates.pilihan_1_id')
            ->where('role', Role::Koor->value)
            ->limit(1);

        // Pilihan 2 koor ID subquery
        $p2KoorQuery = DB::table('users')
            ->select('id')
            ->whereColumn('division_id', 'candidates.pilihan_2_id')
            ->where('role', Role::Koor->value)
            ->limit(1);

        // Averages and counts
        // 1. Number of unique scorers
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('count(distinct interviewer_id)')
                ->whereColumn('slot_id', 'interview_slots.id'),
            'scorers_count'
        );

        // 2. average_all
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('avg(value)')
                ->whereColumn('slot_id', 'interview_slots.id'),
            'average_all'
        );

        // 3. average_primary
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('avg(value)')
                ->whereColumn('slot_id', 'interview_slots.id')
                ->where(function ($query) use ($headId) {
                    $query->where('interviewer_id', $headId)
                          ->orWhere('interviewer_id', DB::raw('(SELECT id FROM users WHERE division_id = candidates.pilihan_1_id AND role = \'koor\' LIMIT 1)'))
                          ->orWhere('interviewer_id', DB::raw('(SELECT id FROM users WHERE division_id = candidates.pilihan_2_id AND role = \'koor\' LIMIT 1)'));
                }),
            'average_primary'
        );

        // 4. average_pilihan_1_koor
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('avg(value)')
                ->whereColumn('slot_id', 'interview_slots.id')
                ->where('interviewer_id', DB::raw('(SELECT id FROM users WHERE division_id = candidates.pilihan_1_id AND role = \'koor\' LIMIT 1)')),
            'average_pilihan_1_koor'
        );

        // 5. average_pilihan_2_koor
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('avg(value)')
                ->whereColumn('slot_id', 'interview_slots.id')
                ->where('interviewer_id', DB::raw('(SELECT id FROM users WHERE division_id = candidates.pilihan_2_id AND role = \'koor\' LIMIT 1)')),
            'average_pilihan_2_koor'
        );

        // 6. average_other_poh
        $q->selectSub(
            DB::table('scores')
                ->selectRaw('avg(value)')
                ->whereColumn('slot_id', 'interview_slots.id')
                ->where(function ($query) use ($headId) {
                    $query->where('interviewer_id', '!=', $headId)
                          ->where('interviewer_id', '!=', DB::raw('COALESCE((SELECT id FROM users WHERE division_id = candidates.pilihan_1_id AND role = \'koor\' LIMIT 1), -1)'))
                          ->where('interviewer_id', '!=', DB::raw('COALESCE((SELECT id FROM users WHERE division_id = candidates.pilihan_2_id AND role = \'koor\' LIMIT 1), -1)'));
                }),
            'average_other_poh'
        );

        // Join interview_slots to make it easier
        $q->leftJoin('interview_slots', 'candidates.id', '=', 'interview_slots.candidate_id');

        return $q;
    }
}
