<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembimbing hafalan.
 *
 * Sengaja tabel sendiri, bukan sekadar user ber-role "muhaffizh": datanya
 * (sanad, pendidikan, tanggal bergabung) tidak ada padanannya di tabel users,
 * dan seorang muhaffizh boleh didata sebelum ia diberi akun login —
 * karena itu user_id nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muhaffizh', function (Blueprint $table) {
            $table->id();

            // Satu akun hanya boleh mewakili satu muhaffizh.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->string('kode', 20)->unique();          // mis. "MHF-001"
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('no_hp', 25)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('pendidikan', 150)->nullable();
            $table->string('sanad_riwayat', 150)->nullable();  // mis. "Hafsh 'an 'Ashim"
            $table->date('tanggal_bergabung')->nullable();

            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('foto')->nullable();
            $table->text('keterangan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'jenis_kelamin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muhaffizh');
    }
};
