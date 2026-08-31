<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keanggotaan santri di sebuah halaqah.
 *
 * Menunjuk ke `pendaftaran`, BUKAN ke `peserta`. Satu orang bisa ikut karantina
 * berkali-kali; kalau keanggotaan melekat pada orangnya, halaqah angkatan lalu
 * dan angkatan sekarang tercampur dan seluruh rekap hafalan ikut salah.
 *
 * Santri boleh dipindah halaqah di tengah program — barisnya tidak dihapus,
 * melainkan ditutup dengan tanggal_keluar supaya riwayat setorannya tetap
 * bisa ditelusuri ke muhaffizh yang membimbingnya saat itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_halaqah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('halaqah_id')->constrained('halaqah')->cascadeOnDelete();
            $table->foreignId('pendaftaran_id')->constrained('pendaftaran')->cascadeOnDelete();

            $table->date('tanggal_bergabung');
            $table->date('tanggal_keluar')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->string('alasan_pindah', 255)->nullable();

            $table->timestamps();

            /*
             | MySQL tidak punya partial unique index, jadi aturan "satu santri
             | hanya boleh aktif di satu halaqah" ditegakkan lewat kolom bayangan:
             | berisi pendaftaran_id selama keanggotaan aktif, NULL setelah ditutup.
             | MySQL mengizinkan NULL berulang pada kolom unik, sehingga riwayat
             | perpindahan tetap boleh menumpuk sementara yang aktif dijamin satu.
             |
             | Nilainya dijaga oleh App\Models\AnggotaHalaqah::booted().
             */
            $table->unsignedBigInteger('kunci_aktif')->nullable()->unique();

            // Satu santri tidak boleh punya dua baris di halaqah yang sama.
            $table->unique(['halaqah_id', 'pendaftaran_id']);
            $table->index(['halaqah_id', 'is_aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_halaqah');
    }
};
