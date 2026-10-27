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
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id('id_pendaftar');
            $table->foreignId('id_user')->unique()->constrained('users', 'id_user')->restrictOnDelete();
            $table->foreignId('id_periode')->constrained('periode_pendaftaran', 'id_periode')->restrictOnDelete();
            $table->string('nama_lengkap', 100);
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('nama_wali', 100);
            $table->string('no_telepon', 15);
            $table->enum('status_daftar', ['diproses', 'diterima', 'ditolak'])->default('diproses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
