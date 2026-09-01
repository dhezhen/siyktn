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
        // Update existing data mapping
        \Illuminate\Support\Facades\DB::table('setoran')->where('kualitas', 'maqbul')->update(['kualitas' => 'jayyid']);
        \Illuminate\Support\Facades\DB::table('setoran')->where('kualitas', 'perlu_diulang')->update(['kualitas' => 'jayyid']);

        // Alter enum safely using raw SQL
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE setoran MODIFY COLUMN kualitas ENUM('jayyid', 'jayyid_jiddan', 'mumtaz') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE setoran MODIFY COLUMN kualitas ENUM('mumtaz', 'jayyid', 'maqbul', 'perlu_diulang') NOT NULL");
    }
};
