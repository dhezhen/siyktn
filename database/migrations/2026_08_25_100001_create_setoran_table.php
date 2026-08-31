<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan setoran hafalan santri.
 *
 * Satuannya HALAMAN, sesuai kebiasaan pencatatan di YKTN. `jumlah_halaman`
 * adalah satu-satunya angka yang dijumlahkan saat rekap; juz, surah, dan ayat
 * hanya konteks. Sengaja tidak menyimpan rentang halaman sekaligus jumlahnya —
 * dua angka yang bisa saling bertentangan hanya akan merusak rekap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran', function (Blueprint $table) {
            $table->id();

            // Konteks lengkap: santri mana, di halaqah mana, angkatan mana.
            $table->foreignId('anggota_halaqah_id')->constrained('anggota_halaqah')->cascadeOnDelete();

            /*
             | Penyimak disimpan EKSPLISIT, bukan diturunkan dari
             | halaqah.muhaffizh_id. Kalau pengampu sebuah halaqah diganti di
             | tengah program, seluruh setoran lama akan diam-diam berpindah
             | atas nama muhaffizh baru — dan itu baru ketahuan saat rekap akhir.
             */
            $table->foreignId('muhaffizh_id')->nullable()->constrained('muhaffizh')->nullOnDelete();

            /*
             | Siapa yang menyimak dan siapa yang mengetik sengaja dipisah.
             | Muhaffizh yang tidak berakun tetap tercatat sebagai penyimak,
             | sementara barisnya dientri operator dari kartu setoran.
             */
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->date('tanggal');
            $table->enum('jenis', ['ziyadah', 'murajaah'])->default('ziyadah');

            // Kelipatan 0,5 halaman lazim dipakai, jadi bukan bilangan bulat.
            $table->decimal('jumlah_halaman', 5, 2);

            $table->unsignedTinyInteger('juz')->nullable();       // 1–30
            $table->string('surah', 60)->nullable();
            $table->unsignedSmallInteger('ayat_dari')->nullable();
            $table->unsignedSmallInteger('ayat_sampai')->nullable();

            $table->enum('kualitas', ['mumtaz', 'jayyid', 'maqbul', 'perlu_diulang'])->default('jayyid');
            $table->text('catatan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Rekap selalu disaring per santri dan per rentang tanggal.
            $table->index(['anggota_halaqah_id', 'tanggal']);
            $table->index(['muhaffizh_id', 'tanggal']);
            $table->index(['tanggal', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran');
    }
};
