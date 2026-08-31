<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan "orang" dari "keikutsertaan".
 *
 * Sebelumnya satu baris `peserta` mewakili keduanya sekaligus, sehingga NIK
 * yang unik ikut mengunci orangnya — alumni tidak bisa mendaftar lagi di
 * angkatan berikutnya.
 *
 * Sesudah migrasi ini:
 *   peserta      = orang, satu baris seumur hidup, dikenali dari NIK
 *   pendaftaran  = keikutsertaan pada satu angkatan, boleh lebih dari satu
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->cascadeOnDelete();
            $table->foreignId('angkatan_id')->constrained('angkatan')->restrictOnDelete();

            $table->string('kode_pendaftaran', 20)->unique();
            $table->string('nomor_induk', 30)->nullable()->unique();

            $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->enum('sumber_pendaftaran', ['mandiri', 'admin'])->default('admin');

            // Status keikutsertaan setelah pendaftaran disetujui.
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif');
            $table->date('tanggal_masuk')->nullable();

            $table->timestamp('didaftarkan_pada')->nullable();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_penolakan')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Satu orang hanya boleh punya satu pendaftaran per angkatan.
            $table->unique(['peserta_id', 'angkatan_id']);
            $table->index(['status_pendaftaran', 'didaftarkan_pada']);
        });

        $this->pindahkanData();

        Schema::table('peserta', function (Blueprint $table) {
            $table->dropIndex(['status_pendaftaran', 'didaftarkan_pada']);

            if (DB::getDriverName() === 'sqlite') {
                $table->dropIndex(['angkatan_id', 'status']);
                $table->dropUnique(['kode_pendaftaran']);
                $table->dropUnique(['nomor_induk']);
                $table->dropConstrainedForeignId('angkatan_id');
            } else {
                $table->dropConstrainedForeignId('angkatan_id');
                $table->dropIndex(['angkatan_id', 'status']);
                $table->dropUnique(['kode_pendaftaran']);
                $table->dropUnique(['nomor_induk']);
            }

            $table->dropConstrainedForeignId('ditinjau_oleh');
            $table->dropColumn([
                'kode_pendaftaran', 'nomor_induk', 'status', 'status_pendaftaran',
                'sumber_pendaftaran', 'tanggal_masuk', 'didaftarkan_pada',
                'ditinjau_pada', 'alasan_penolakan',
            ]);
        });

        Schema::table('peserta', function (Blueprint $table) {
            // Sebagian orang memang tidak boleh mendaftar lagi. Lebih baik
            // tercatat daripada mengandalkan ingatan petugas.
            $table->boolean('boleh_mendaftar_lagi')->default(true)->after('ktp_path');
            $table->text('alasan_cekal')->nullable()->after('boleh_mendaftar_lagi');
        });
    }

    /**
     * Setiap baris peserta lama menjadi satu baris pendaftaran, sementara
     * baris peserta itu sendiri dipertahankan sebagai data orangnya.
     */
    protected function pindahkanData(): void
    {
        DB::table('peserta')->orderBy('id')->chunkById(200, function ($rows) {
            $baris = [];

            foreach ($rows as $peserta) {
                $baris[] = [
                    'peserta_id' => $peserta->id,
                    'angkatan_id' => $peserta->angkatan_id,
                    // Baris lama tanpa kode diberi awalan MIG- supaya tidak
                    // pernah bentrok dengan deret REG- yang sudah terpakai.
                    'kode_pendaftaran' => $peserta->kode_pendaftaran ?: sprintf('MIG-%05d', $peserta->id),
                    'nomor_induk' => $peserta->nomor_induk,
                    'status_pendaftaran' => $peserta->status_pendaftaran,
                    'sumber_pendaftaran' => $peserta->sumber_pendaftaran,
                    'status' => $peserta->status,
                    'tanggal_masuk' => $peserta->tanggal_masuk,
                    'didaftarkan_pada' => $peserta->didaftarkan_pada ?: $peserta->created_at,
                    'ditinjau_pada' => $peserta->ditinjau_pada,
                    'ditinjau_oleh' => $peserta->ditinjau_oleh,
                    'alasan_penolakan' => $peserta->alasan_penolakan,
                    'created_at' => $peserta->created_at,
                    'updated_at' => $peserta->updated_at,
                    'deleted_at' => $peserta->deleted_at,
                ];
            }

            if ($baris !== []) {
                DB::table('pendaftaran')->insert($baris);
            }
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropColumn(['boleh_mendaftar_lagi', 'alasan_cekal']);

            $table->foreignId('angkatan_id')->nullable()->constrained('angkatan');
            $table->string('kode_pendaftaran', 20)->nullable();
            $table->string('nomor_induk', 30)->nullable();
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif');
            $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])->default('disetujui');
            $table->enum('sumber_pendaftaran', ['mandiri', 'admin'])->default('admin');
            $table->date('tanggal_masuk')->nullable();
            $table->timestamp('didaftarkan_pada')->nullable();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_penolakan')->nullable();
        });

        Schema::dropIfExists('pendaftaran');
    }
};
