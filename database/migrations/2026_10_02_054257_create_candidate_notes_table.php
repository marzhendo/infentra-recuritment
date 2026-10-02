<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->unique(['candidate_id', 'author_id']);
            $table->index('candidate_id');
            $table->index('author_id');
        });

        // Migrate existing notes from scores.
        // We group by slot->candidate and interviewer to merge multiple aspect notes if they exist.
        // If there are multiple notes for the same interviewer+slot, we concatenate them.
        
        $scoresWithNotes = DB::table('scores')
            ->join('interview_slots', 'scores.slot_id', '=', 'interview_slots.id')
            ->whereNotNull('scores.note')
            ->where('scores.note', '!=', '')
            ->select('interview_slots.candidate_id', 'scores.interviewer_id', 'scores.note', 'scores.created_at', 'scores.updated_at')
            ->orderBy('scores.created_at')
            ->get();

        $notesToInsert = [];
        foreach ($scoresWithNotes as $score) {
            $key = $score->candidate_id . '_' . $score->interviewer_id;
            if (!isset($notesToInsert[$key])) {
                $notesToInsert[$key] = [
                    'candidate_id' => $score->candidate_id,
                    'author_id' => $score->interviewer_id,
                    'body' => $score->note,
                    'created_at' => $score->created_at,
                    'updated_at' => $score->updated_at,
                ];
            } else {
                $notesToInsert[$key]['body'] .= "\n\n" . $score->note;
                $notesToInsert[$key]['updated_at'] = max($notesToInsert[$key]['updated_at'], $score->updated_at);
            }
        }

        foreach ($notesToInsert as $noteData) {
            DB::table('candidate_notes')->insertOrIgnore($noteData);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_notes');
    }
};
