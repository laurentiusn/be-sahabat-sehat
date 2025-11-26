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
        Schema::create('monitor_imt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('id_user')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('tanggal');
            $table->decimal('tinggi_badan', 5, 2);
            $table->decimal('massa_badan', 5, 2);
            $table->decimal('imt', 5, 2);
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_imt');
    }
};
