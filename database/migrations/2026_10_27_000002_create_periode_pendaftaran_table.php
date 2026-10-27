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
        Schema::create('periode_pendaftaran', function (Blueprint $table) {
            $table->id('id_periode');
            $table->string('nama_periode', 50);
            $table->date('tanggal_buka');
            $table->date('tanggal_tutup');
            $table->enum('status_periode', ['dibuka', 'ditutup'])->default('ditutup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_pendaftaran');
    }
};
