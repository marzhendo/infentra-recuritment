<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->default('DC-302');
            $table->foreignId('candidate_id')->nullable()->unique()->constrained('candidates')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_slots');
    }
};
