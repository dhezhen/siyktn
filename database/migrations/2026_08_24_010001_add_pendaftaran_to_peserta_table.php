<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            // Nomor induk baru diberikan saat pendaftaran disetujui.
            $table->string('nomor_induk', 30)->nullable()->change();

            $table->string('kode_pendaftaran', 20)->nullable()->unique()->after('id');
            $table->string('email', 150)->nullable()->after('no_hp');
            $table->char('nik', 16)->nullable()->unique()->after('nama');
            $table->string('ktp_path')->nullable()->after('foto');

            $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])
                ->default('disetujui')
                ->after('status');

            $table->enum('sumber_pendaftaran', ['mandiri', 'admin'])
                ->default('admin')
                ->after('status_pendaftaran');

            $table->timestamp('didaftarkan_pada')->nullable()->after('sumber_pendaftaran');
            $table->timestamp('ditinjau_pada')->nullable()->after('didaftarkan_pada');
            $table->foreignId('ditinjau_oleh')->nullable()->after('ditinjau_pada')
                ->constrained('users')->nullOnDelete();
            $table->text('alasan_penolakan')->nullable()->after('ditinjau_oleh');

            $table->index(['status_pendaftaran', 'didaftarkan_pada']);
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropIndex(['status_pendaftaran', 'didaftarkan_pada']);
            $table->dropConstrainedForeignId('ditinjau_oleh');
            $table->dropColumn([
                'kode_pendaftaran', 'email', 'nik', 'ktp_path',
                'status_pendaftaran', 'sumber_pendaftaran',
                'didaftarkan_pada', 'ditinjau_pada', 'alasan_penolakan',
            ]);
        });
    }
};
