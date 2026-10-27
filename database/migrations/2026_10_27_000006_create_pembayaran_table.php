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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->foreignId('id_tagihan')->unique()->constrained('tagihan', 'id_tagihan')->restrictOnDelete();
            $table->date('tanggal_bayar')->nullable();
            $table->decimal('jumlah_bayar', 12, 2);
            $table->enum('status_bayar', ['menunggu', 'berhasil', 'gagal', 'kedaluwarsa'])->default('menunggu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
