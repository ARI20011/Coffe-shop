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
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_transaksi')->constrained('transaksi', 'id_transaksi')->cascadeOnDelete();
            $table->foreignId('id_produk')->nullable()->constrained('produk', 'id_produk')->nullOnDelete();
            $table->string('nama_produk', 150);
            $table->unsignedBigInteger('harga_satuan');
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('subtotal_harga');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
