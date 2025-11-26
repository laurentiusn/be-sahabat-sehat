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
        Schema::create('obat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relasi ke User (Pasien)
            $table->foreignUuid('id_user')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('nama_obat');
            $table->string('dosis'); // Contoh: "1 Tablet", "5 ml"
            
            // Rentang Tanggal Rencana Awal
            $table->date('start_date');
            $table->date('end_date');
            
            // Waktu Konsumsi
            $table->enum('waktu', ['pagi', 'siang', 'sore', 'malam']);
            
            $table->string('keterangan')->nullable(); 

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obat');
    }
};
