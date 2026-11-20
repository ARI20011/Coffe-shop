<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MenuCatalog
{
    /**
     * @return list<array{id_menu: int, id_produk: int, nama_menu: string, kategori: string, harga: int, stok_realtime: int, foto_url: string}>
     */
    public function all(): array
    {
        return DB::table('produk')
            ->leftJoin('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori')
            ->select('produk.*', 'kategori.nama_kategori')
            ->orderBy('produk.id_produk')
            ->get()
            ->map(static fn (object $product): array => [
                'id_menu' => (int) $product->id_produk,
                'id_produk' => (int) $product->id_produk,
                'nama_menu' => $product->nama_produk,
                'kategori' => $product->nama_kategori ?? 'Lainnya',
                'harga' => (int) $product->harga,
                'stok_realtime' => (int) $product->stok,
                'foto_url' => $product->gambar ?: 'resources/img/coffee-cup.png',
            ])
            ->all();
    }

    /**
     * @return array{id_menu: int, id_produk: int, nama_menu: string, kategori: string, harga: int, stok_realtime: int, foto_url: string}|null
     */
    public function find(int $menuId): ?array
    {
        foreach ($this->all() as $item) {
            if ($item['id_menu'] === $menuId) {
                return $item;
            }
        }

        return null;
    }
}
