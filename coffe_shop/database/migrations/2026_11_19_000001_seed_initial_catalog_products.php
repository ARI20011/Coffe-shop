<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('produk')->exists()) {
            return;
        }

        $now = now();
        $categoryIds = [];

        foreach (['Kopi', 'Non-Kopi', 'Camilan'] as $categoryName) {
            DB::table('kategori')->insertOrIgnore([
                'nama_kategori' => $categoryName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $categoryIds[$categoryName] = DB::table('kategori')
                ->where('nama_kategori', $categoryName)
                ->value('id_kategori');
        }

        $products = [
            ['Kopi', 'Kopi Susu Kala', 'Espresso dan susu segar dengan rasa seimbang.', 18000, 12, 'resources/img/coffee-cup.png'],
            ['Kopi', 'Americano', 'Espresso dengan karakter biji kopi pilihan.', 15000, 5, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80'],
            ['Non-Kopi', 'Matcha Aira', 'Matcha lembut dengan susu segar.', 22000, 0, 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?auto=format&fit=crop&w=900&q=80'],
            ['Non-Kopi', 'Cokelat Hangat', 'Cokelat hangat dengan rasa yang kaya.', 20000, 8, 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=900&q=80'],
            ['Kopi', 'Signature Iced Latte', 'Espresso dan susu dingin dengan rasa creamy.', 28000, 10, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=900&q=80'],
            ['Kopi', 'Caramel Macchiato', 'Espresso, susu, dan sentuhan karamel.', 32000, 8, 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?auto=format&fit=crop&w=900&q=80'],
            ['Kopi', 'Vanilla Cold Brew', 'Cold brew segar dengan aroma vanilla.', 30000, 7, 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=900&q=80'],
            ['Kopi', 'Cappuccino', 'Espresso dengan foam susu lembut.', 26000, 10, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=900&q=80'],
            ['Non-Kopi', 'Matcha Latte', 'Matcha premium dan susu segar.', 27000, 6, 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=900&q=80'],
            ['Non-Kopi', 'Chocolate Frappe', 'Minuman cokelat dingin yang lembut.', 29000, 9, 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=900&q=80'],
            ['Camilan', 'Croissant Butter', 'Croissant renyah dengan butter pilihan.', 24000, 7, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=900&q=80'],
            ['Camilan', 'Banana Bread', 'Roti pisang lembut untuk teman ngopi.', 22000, 5, 'https://images.unsplash.com/photo-1608198093002-ad4e00548460?auto=format&fit=crop&w=900&q=80'],
        ];

        foreach ($products as [$category, $name, $description, $price, $stock, $image]) {
            DB::table('produk')->insert([
                'id_kategori' => $categoryIds[$category],
                'nama_produk' => $name,
                'deskripsi' => $description,
                'harga' => $price,
                'stok' => $stock,
                'gambar' => $image,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void {}
};
