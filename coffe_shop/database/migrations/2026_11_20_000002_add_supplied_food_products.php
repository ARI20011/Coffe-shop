<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $categoryId = DB::table('kategori')->where('nama_kategori', 'Camilan')->value('id_kategori');

        if ($categoryId === null) {
            $categoryId = DB::table('kategori')->insertGetId([
                'nama_kategori' => 'Camilan',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_kategori');
        }

        DB::table('produk')
            ->where('nama_produk', 'Banana Bread')
            ->update([
                'gambar' => 'resources/img/banana-bread.png',
                'updated_at' => $now,
            ]);

        $products = [
            [
                'nama_produk' => 'Club Sandwich',
                'deskripsi' => 'Roti panggang lapis daging, keju, selada, tomat, dan saus.',
                'harga' => 35000,
                'stok' => 10,
                'gambar' => 'resources/img/club-sandwich.png',
            ],
            [
                'nama_produk' => 'Chocolate Croissant',
                'deskripsi' => 'Pastry berlapis renyah dengan isian cokelat.',
                'harga' => 28000,
                'stok' => 10,
                'gambar' => 'resources/img/chocolate-croissant.png',
            ],
        ];

        foreach ($products as $product) {
            if (DB::table('produk')->where('nama_produk', $product['nama_produk'])->exists()) {
                continue;
            }

            DB::table('produk')->insert([
                ...$product,
                'id_kategori' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void {}
};
