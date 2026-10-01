<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nim')->unique()->nullable();
            $table->string('jabatan')->nullable();
            $table->enum('role', ['admin', 'koor'])->default('koor');
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn(['nim', 'jabatan', 'role', 'division_id']);
        });
    }
};
