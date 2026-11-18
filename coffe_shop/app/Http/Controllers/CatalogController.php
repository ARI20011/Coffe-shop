<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $menu = [
            [
                'id_menu' => 1,
                'nama_menu' => 'Kopi Susu Kala',
                'kategori' => 'Kopi',
                'harga' => 18000,
                'stok_realtime' => 12,
                'foto_url' => 'https://images.unsplash.com/photo-1572449043416-55f4685c9bb7?auto=format&fit=crop&w=900&q=80',
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

        $kategori = $request->string('kategori')->toString();
        if ($kategori && $kategori !== 'Semua') {
            $menu = array_values(array_filter(
                $menu,
                fn (array $item): bool => $item['kategori'] === $kategori
            ));
        }

        return view('catalog.index', [
            'menu' => collect($menu),
            'kategoriAktif' => $kategori ?: 'Semua',
        ]);
    }
}
