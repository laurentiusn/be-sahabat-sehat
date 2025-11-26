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
        Schema::create('indikator', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['td', 'gd', 'imt']);
            $table->string('status');
            $table->json('range');
            $table->string('color_hex');
            $table->timestamps();
            $table->softDeletes();
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indikator');
    }
};
