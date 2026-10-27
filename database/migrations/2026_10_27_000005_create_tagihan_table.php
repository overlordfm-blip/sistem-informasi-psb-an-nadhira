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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id('id_tagihan');
            $table->foreignId('id_pendaftar')->constrained('pendaftar', 'id_pendaftar')->restrictOnDelete();
            $table->enum('jenis_tagihan', ['pendaftaran', 'syahriyah']);
            $table->decimal('nominal', 12, 2);
            $table->enum('status_tagihan', ['belum_dibayar', 'lunas'])->default('belum_dibayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
