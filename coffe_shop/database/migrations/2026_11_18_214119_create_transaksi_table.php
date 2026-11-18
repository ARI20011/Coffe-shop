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
            $table->foreignId('id_pelanggan')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_kasir')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_meja')->nullable()->constrained('meja', 'id_meja')->nullOnDelete();
            $table->dateTime('tanggal_transaksi');
            $table->unsignedBigInteger('total_harga');
            $table->string('status_pesanan', 30)->default('menunggu');
            $table->timestamps();
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
