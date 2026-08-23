<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('angkatan_id')->constrained('angkatan')->restrictOnDelete();

            $table->string('nomor_induk', 30)->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 80)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp', 25)->nullable();

            $table->string('nama_wali', 100)->nullable();
            $table->string('no_hp_wali', 25)->nullable();

            $table->date('tanggal_masuk')->nullable();
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif');
            $table->string('foto')->nullable();

            // Dihubungkan ke akun bila peserta diberi akses login.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['angkatan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta');
    }
};
