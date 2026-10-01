<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_slots', function (Blueprint $table) {
            if (Schema::hasColumn('interview_slots', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('interview_slots', 'room')) {
                $table->dropColumn('room');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interview_slots', function (Blueprint $table) {
            $table->date('date')->nullable();
            $table->string('room')->nullable();
        });
    }
};
