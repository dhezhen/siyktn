<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->enum('status_kehadiran', ['belum_hadir', 'hadir'])->default('belum_hadir')->after('status_pendaftaran');
            $table->timestamp('waktu_kehadiran')->nullable()->after('status_kehadiran');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete()->after('waktu_kehadiran');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diverifikasi_oleh');
            $table->dropColumn(['status_kehadiran', 'waktu_kehadiran']);
        });
    }
};
