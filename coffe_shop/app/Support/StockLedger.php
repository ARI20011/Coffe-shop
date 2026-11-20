<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockLedger
{
    private const MOVEMENTS = ['stok_awal', 'pembelian', 'pembatalan', 'penyesuaian_masuk', 'penyesuaian_keluar'];

    public function record(
        int $productId,
        string $productName,
        string $movement,
        int $quantity,
        ?int $actorId,
    ): void {
        if (! in_array($movement, self::MOVEMENTS, true) || $quantity < 1) {
            throw new InvalidArgumentException('Pergerakan stok atau jumlah tidak valid.');
        }

        DB::table('riwayat_stok')->insert([
            'id_produk' => $productId,
            'nama_bahan' => $productName,
            'tipe_pergerakan' => $movement,
            'jumlah' => $quantity,
            'satuan' => 'pcs',
            'tanggal_catat' => now(),
            'id_admin' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
