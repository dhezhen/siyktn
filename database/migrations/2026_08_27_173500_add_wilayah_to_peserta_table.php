<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->enum('kewarganegaraan', ['WNI', 'WNA'])->default('WNI')->after('nik');
            $table->string('negara', 100)->default('Indonesia')->after('kewarganegaraan');
            $table->string('provinsi', 100)->nullable()->after('negara');
            $table->string('kabupaten_kota', 100)->nullable()->after('provinsi');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropColumn(['kewarganegaraan', 'negara', 'provinsi', 'kabupaten_kota']);
        });
    }
};
