<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('angkatan', function (Blueprint $table) {
            $table->unsignedSmallInteger('kuota_putra')->default(0)->after('kuota');
            $table->unsignedSmallInteger('kuota_putri')->default(0)->after('kuota_putra');
        });
    }

    public function down(): void
    {
        Schema::table('angkatan', function (Blueprint $table) {
            $table->dropColumn(['kuota_putra', 'kuota_putri']);
        });
    }
};
