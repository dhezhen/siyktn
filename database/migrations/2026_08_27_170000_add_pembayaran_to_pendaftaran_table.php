<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->string('paket_program')->nullable()->after('sumber_pendaftaran');
            $table->decimal('biaya_program', 12, 2)->default(0)->after('paket_program');
            $table->decimal('biaya_pendaftaran', 10, 2)->default(100000)->after('biaya_program');
            $table->string('status_pembayaran_pendaftaran', 20)->default('pending')->after('biaya_pendaftaran');
            $table->string('status_pembayaran_program', 20)->default('pending')->after('status_pembayaran_pendaftaran');
            $table->string('bukti_pembayaran_path')->nullable()->after('status_pembayaran_program');
            $table->text('catatan_pembayaran')->nullable()->after('bukti_pembayaran_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->dropColumn([
                'paket_program',
                'biaya_program',
                'biaya_pendaftaran',
                'status_pembayaran_pendaftaran',
                'status_pembayaran_program',
                'bukti_pembayaran_path',
                'catatan_pembayaran',
            ]);
        });
    }
};
