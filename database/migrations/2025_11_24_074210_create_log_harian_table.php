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
        Schema::create('log_harian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke User
            $table->foreignUuid('id_user')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Penanda Tanggal (Satu user hanya punya 1 log_harian per tanggal)
            $table->date('tanggal');

            $table->timestamps();
            
            // Unique constraint: User tidak bisa punya 2 row log_harian di tanggal yang sama
            $table->unique(['id_user', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_harian');
    }
};
