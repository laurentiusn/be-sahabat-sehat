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
        Schema::create('obat_log', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relasi ke Master Obat
            $table->foreignUuid('id_obat')
                  ->constrained('obat')
                  ->cascadeOnDelete();

            // Tanggal spesifik jadwal minum (Tgl 1, Tgl 2, dst)
            $table->date('jadwal_konsumsi');
            
            // Status kepatuhan
            $table->enum('status', ['sudah', 'belum'])->default('belum');
            
            // Mencatat kapan admin/pasien klik tombol "Sudah"
            $table->dateTime('waktu_aktual_konsumsi')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Mencegah duplikasi log untuk obat yang sama di tanggal yang sama
            $table->unique(['id_obat', 'jadwal_konsumsi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obat_log');
    }
};
