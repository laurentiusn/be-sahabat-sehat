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
        Schema::create('log_makanan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke Parent (Log Harian)
            $table->foreignUuid('id_log_harian')
                  ->constrained('log_harian')
                  ->cascadeOnDelete();

            $table->string('nama_makanan');
            $table->enum('type', ['utama', 'selingan']); // Makanan Utama / Selingan
            $table->time('jam_makan')->nullable(); // Opsional: mencatat jam spesifik

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_makanan');
    }
};
