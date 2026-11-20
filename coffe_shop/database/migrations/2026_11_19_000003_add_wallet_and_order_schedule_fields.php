<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('saldo')->default(0);
        });

        DB::table('users')
            ->where('role', 'pelanggan')
            ->update(['saldo' => 50000]);

        Schema::table('transaksi', function (Blueprint $table): void {
            $table->string('metode_pembayaran', 32)->nullable();
            $table->string('status_pembayaran', 20)->default('menunggu');
            $table->dateTime('waktu_tiba')->nullable();
            $table->dateTime('waktu_pengambilan')->nullable();
        });

        DB::table('transaksi')->update([
            'metode_pembayaran' => 'data_lama',
            'status_pembayaran' => 'berhasil',
        ]);

        $occupiedTableIds = DB::table('transaksi')
            ->whereNotNull('id_meja')
            ->whereNotIn('status_pesanan', ['completed', 'cancelled'])
            ->pluck('id_meja');
        if ($occupiedTableIds->isNotEmpty()) {
            DB::table('meja')->whereIn('id_meja', $occupiedTableIds)->update([
                'status_meja' => 'dipesan',
                'updated_at' => now(),
            ]);
        }

        Schema::create('top_up', function (Blueprint $table): void {
            $table->id('id_topup');
            $table->foreignId('id_pelanggan')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah');
            $table->string('metode_pembayaran', 16);
            $table->string('status_topup', 16)->default('berhasil');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_up');

        Schema::table('transaksi', function (Blueprint $table): void {
            $table->dropColumn(['metode_pembayaran', 'status_pembayaran', 'waktu_tiba', 'waktu_pengambilan']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('saldo');
        });
    }
};
