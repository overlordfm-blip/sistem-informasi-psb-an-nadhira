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
        Schema::create('informasi_pondok', function (Blueprint $table) {
            $table->id('id_informasi');
            $table->foreignId('id_user')->constrained('users', 'id_user')->restrictOnDelete();
            $table->string('judul', 150);
            $table->text('isi');
            $table->date('diperbarui');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informasi_pondok');
    }
};
