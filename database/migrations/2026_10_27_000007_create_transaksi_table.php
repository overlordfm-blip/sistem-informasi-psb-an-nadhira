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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->foreignId('id_pembayaran')->constrained('pembayaran', 'id_pembayaran')->restrictOnDelete();
            $table->string('order_id', 50)->unique();
            $table->string('token', 100);
            $table->string('metode', 30)->nullable();
            $table->enum('status_transaksi', ['menunggu', 'berhasil', 'gagal', 'kedaluwarsa'])->default('menunggu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
