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
        Schema::create('riwayat_stok', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->string('nama_bahan', 150);
            $table->string('tipe_pergerakan', 30);
            $table->decimal('jumlah', 12, 2);
            $table->string('satuan', 30);
            $table->dateTime('tanggal_catat');
            $table->foreignId('id_admin')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_stok');
    }
};
