<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('interview_slots')->cascadeOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rubric_aspect_id')->constrained('rubric_aspects')->cascadeOnDelete();
            $table->unsignedTinyInteger('value');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['slot_id', 'interviewer_id', 'rubric_aspect_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
