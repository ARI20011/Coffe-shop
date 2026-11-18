<?php

namespace App\Support;

class MenuCatalog
{
    /**
     * @return list<array{id_menu: int, nama_menu: string, kategori: string, harga: int, stok_realtime: int, foto_url: string}>
     */
    public function all(): array
    {
        return [
            [
                'id_menu' => 1,
                'nama_menu' => 'Kopi Susu Kala',
                'kategori' => 'Kopi',
                'harga' => 18000,
                'stok_realtime' => 12,
                'foto_url' => 'resources/img/coffee-cup.png',
            ],
            [
                'id_menu' => 2,
                'nama_menu' => 'Americano',
                'kategori' => 'Kopi',
                'harga' => 15000,
                'stok_realtime' => 5,
                'foto_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id_menu' => 3,
                'nama_menu' => 'Matcha Aira',
                'kategori' => 'Non-Kopi',
                'harga' => 22000,
                'stok_realtime' => 0,
                'foto_url' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'id_menu' => 4,
                'nama_menu' => 'Cokelat Hangat',
                'kategori' => 'Non-Kopi',
                'harga' => 20000,
                'stok_realtime' => 8,
                'foto_url' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=900&q=80',
            ],
        ];
    }

    /**
     * @return array{id_menu: int, nama_menu: string, kategori: string, harga: int, stok_realtime: int, foto_url: string}|null
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
