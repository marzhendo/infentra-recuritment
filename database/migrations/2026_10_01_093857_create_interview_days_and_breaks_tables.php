<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->time('starts_at');
            $table->integer('slot_minutes')->default(10);
            $table->string('room')->default('DC-302');
            $table->time('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('break_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_day_id')->constrained('interview_days')->cascadeOnDelete();
            $table->string('label');
            $table->time('starts_at');
            $table->integer('duration_minutes');
            $table->timestamps();
        });

        Schema::table('interview_slots', function (Blueprint $table) {
            $table->foreignId('interview_day_id')->nullable()->constrained('interview_days')->cascadeOnDelete();
            $table->boolean('is_locked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('interview_slots', function (Blueprint $table) {
            $table->dropForeign(['interview_day_id']);
            $table->dropColumn(['interview_day_id', 'is_locked']);
        });
        Schema::dropIfExists('break_blocks');
        Schema::dropIfExists('interview_days');
    }
};
