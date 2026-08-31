<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelompok halaqah dalam satu angkatan.
 *
 * Dokumen analisis menyebut halaqah sebagai "entitas junction" — itu keliru.
 * Halaqah adalah entitas nyata: ia punya nama, kuota, ruang, dan seorang
 * muhaffizh pengampu. Yang benar-benar junction adalah anggota_halaqah,
 * karena keanggotaan santri punya tanggal masuk dan tanggal keluar sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halaqah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('angkatan_id')->constrained('angkatan')->cascadeOnDelete();

            // Satu muhaffizh boleh mengampu lebih dari satu halaqah.
            $table->foreignId('muhaffizh_id')->nullable()->constrained('muhaffizh')->nullOnDelete();

            $table->string('kode', 30);                    // mis. "H-01"
            $table->string('nama');                        // mis. "Halaqah Al-Fatih"
            $table->enum('jenis_kelamin', ['L', 'P']);     // halaqah ikhwan / akhwat
            $table->unsignedSmallInteger('kuota')->default(0);
            $table->string('ruang', 60)->nullable();
            $table->string('jadwal', 120)->nullable();     // mis. "Ba'da Shubuh & Ba'da Ashar"
            $table->boolean('is_aktif')->default(true);
            $table->text('keterangan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Kode cukup unik di dalam angkatannya — "H-01" boleh dipakai ulang
            // tiap angkatan, persis seperti kebiasaan penamaan di lapangan.
            $table->unique(['angkatan_id', 'kode']);
            $table->index(['angkatan_id', 'is_aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halaqah');
    }
};
