<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CandidateStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nim')->nullable();
            $table->string('angkatan')->nullable();
            $table->foreignId('pilihan_1_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('pilihan_2_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->text('file_certificate')->nullable();
            $table->text('file_cv')->nullable();
            $table->text('file_portfolio')->nullable();
            $table->string('form_timestamp')->nullable();
            $table->string('status')->default(CandidateStatus::Terdaftar->value);
            $table->boolean('is_hmif')->default(false);
            $table->timestamps();

            // Unique key for idempotent re-import
            $table->unique(['form_timestamp', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
