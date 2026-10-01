<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['candidate_id', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
