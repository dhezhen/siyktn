<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('phone', 25)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');

            // Nonaktifkan user, jangan dihapus, agar relasi ke data lain tetap utuh.
            $table->boolean('is_active')->default(true)->after('avatar');

            // Dipakai saat admin mereset password: user dipaksa ganti saat login.
            $table->boolean('must_change_password')->default(false)->after('is_active');

            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'avatar', 'is_active',
                'must_change_password', 'last_login_at', 'last_login_ip', 'deleted_at',
            ]);
        });
    }
};
