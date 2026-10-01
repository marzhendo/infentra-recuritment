<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Drop old unique index and column
            $table->dropUnique(['form_timestamp', 'name']);
            $table->dropColumn('file_certificate');

            // Change form_timestamp to datetime (since sqlite doesn't easily alter column types from string to datetime without dropping, we can just drop and recreate it, or use change() if sqlite allows. In sqlite, alter column types is limited, but we can try)
            // Wait, doctrine/dbal might be needed. Let's just drop it and recreate it to be safe, since there is no prod data yet.
            $table->dropColumn('form_timestamp');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dateTime('form_timestamp')->nullable();
            
            $table->text('file_cert_pkkmb')->nullable();
            $table->text('file_cert_wpi')->nullable();
            $table->string('whatsapp')->nullable();
            $table->json('form_data')->nullable();
            $table->string('import_key')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn(['form_timestamp', 'file_cert_pkkmb', 'file_cert_wpi', 'whatsapp', 'form_data', 'import_key']);
            $table->string('form_timestamp')->nullable();
            $table->text('file_certificate')->nullable();
            $table->unique(['form_timestamp', 'name']);
        });
    }
};
