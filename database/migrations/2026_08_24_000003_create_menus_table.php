<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();

            $table->string('title');
            $table->string('icon')->nullable();

            // route = tautan internal, url = tautan luar, header/divider = pemisah tampilan
            $table->enum('type', ['route', 'url', 'header', 'divider'])->default('route');
            $table->string('route')->nullable();
            $table->string('url')->nullable();
            $table->string('target', 10)->default('_self');

            // Menu tampil hanya bila permission ini dimiliki. Null = tampil untuk semua.
            $table->string('permission')->nullable();

            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
