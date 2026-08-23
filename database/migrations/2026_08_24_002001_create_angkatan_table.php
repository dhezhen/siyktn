<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('angkatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                       // mis. "Angkatan 12"
            $table->string('kode', 20)->unique();          // mis. "AK-12"
            $table->year('tahun');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedSmallInteger('kuota')->default(0);
            $table->enum('status', ['persiapan', 'berjalan', 'selesai'])->default('persiapan');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('angkatan');
    }
};
