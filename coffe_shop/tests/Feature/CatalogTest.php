<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_page_displays_menu_and_stock_status(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Popular Brews');
        $response->assertSee('Kopi yang diracik');
        $response->assertSee('Kenapa memilih kami');
        $response->assertSee('Diskon 30% untuk pesanan dine-in');
        $response->assertSee('Rp 18.000');
        $response->assertSee('Stok menipis');
        $response->assertSee('Habis');
        $response->assertSee('Tambah');
    }

    public function test_about_page_shows_ende_location_and_our_best_employees(): void
    {
        User::factory()->create([
            'name' => 'Nadia Ende',
            'role' => 'kasir',
        ]);

        $this->get(route('about.index'))
            ->assertOk()
            ->assertSee('Kisah di balik setiap cangkir')
            ->assertSee('Filosofi')
            ->assertSee('Bertemu karyawan')
            ->assertSee('Nadia Ende')
            ->assertSee('Ende, Nusa Tenggara Timur')
            ->assertSee('openstreetmap.org/export/embed.html');
    }

    public function test_catalog_category_filter_works(): void
    {
        $response = $this->get(route('catalog.index', ['kategori' => 'Kopi']));

        $response->assertOk();
        $response->assertSee('Kopi Susu Kala');
        $response->assertDontSee('Matcha Aira');
    }

    public function test_home_menu_navigation_keeps_the_best_sellers_and_opens_full_menu_page(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee(route('dashboard.index', ['page' => 'menu']))
            ->assertSee('Popular Brews')
            ->assertSee('Kopi Susu Kala');

        $this->get(route('dashboard.index', ['page' => 'menu']))
            ->assertOk()
            ->assertSee('Tampilan produk')
            ->assertSee('name="q"', false)
            ->assertSeeInOrder(['Cari menu', 'Cari menu terlaris?'])
            ->assertSee('menu terlaris');

        $this->get(route('dashboard.index', ['page' => 'menu', 'menu_page' => 2]))
            ->assertOk()
            ->assertSee('Signature Iced Latte')
            ->assertSee('Cappuccino');

        $this->get(route('dashboard.index', ['page' => 'menu', 'menu_page' => 3]))
            ->assertOk()
            ->assertSee('Croissant Butter');
    }

    public function test_homepage_orders_products_by_completed_sales_and_labels_best_sellers(): void
    {
        $customer = User::factory()->create();
        $bestSellerId = DB::table('produk')->insertGetId([
            'nama_produk' => 'Produk Sangat Laris',
            'harga' => 15000,
            'stok' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_produk');
        $secondSellerId = DB::table('produk')->insertGetId([
            'nama_produk' => 'Produk Laris Kedua',
            'harga' => 12000,
            'stok' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_produk');

        foreach ([[$bestSellerId, 5], [$secondSellerId, 3]] as [$productId, $quantity]) {
            $transactionId = DB::table('transaksi')->insertGetId([
                'id_pelanggan' => $customer->id,
                'tanggal_transaksi' => now(),
                'total_harga' => 100000,
                'status_pesanan' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_transaksi');
            DB::table('detail_transaksi')->insert([
                'id_transaksi' => $transactionId,
                'id_produk' => $productId,
                'nama_produk' => $productId === $bestSellerId ? 'Produk Sangat Laris' : 'Produk Laris Kedua',
                'harga_satuan' => 15000,
                'jumlah' => $quantity,
                'subtotal_harga' => $quantity * 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Menu terlaris pelanggan')
            ->assertSeeInOrder(['Produk Sangat Laris', 'Produk Laris Kedua'])
            ->assertSee('Terlaris');
    }

    public function test_menu_page_searches_and_filters_the_full_menu(): void
    {
        $this->get(route('dashboard.index', ['page' => 'menu', 'q' => 'Croissant']))
            ->assertOk()
            ->assertSee('Croissant Butter')
            ->assertDontSee('Americano');

        $this->get(route('dashboard.index', ['page' => 'menu', 'kategori' => 'Camilan']))
            ->assertOk()
            ->assertSee('Croissant Butter')
            ->assertSee('Banana Bread')
            ->assertDontSee('Americano');
    }

    public function test_full_menu_uses_pagination_and_keeps_search_filters_in_page_links(): void
    {
        foreach (range(1, 9) as $number) {
            DB::table('produk')->insert([
                'nama_produk' => 'Kopi Pagination '.$number,
                'harga' => 10000,
                'stok' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->get(route('dashboard.index', ['page' => 'menu', 'q' => 'Kopi']))
            ->assertOk()
            ->assertSee('menu_page=2')
            ->assertSee('q=Kopi');
    }

    public function test_full_menu_grid_options_change_the_number_of_products_per_page(): void
    {
        $twoColumns = $this->get(route('dashboard.index', ['page' => 'menu', 'columns' => 2]));
        $twoColumns->assertOk()->assertSee('style="--menu-columns: 2"', false);
        $this->assertSame(4, substr_count($twoColumns->getContent(), '<article class="menu-card"'));

        $threeColumns = $this->get(route('dashboard.index', ['page' => 'menu', 'columns' => 3]));
        $threeColumns->assertOk()->assertSee('style="--menu-columns: 3"', false);
        $this->assertSame(9, substr_count($threeColumns->getContent(), '<article class="menu-card"'));

        $this->get(route('dashboard.index', ['page' => 'menu', 'columns' => 3, 'menu_page' => 2]))
            ->assertOk()
            ->assertSee('aria-current="page">2', false);
    }

    public function test_catalog_includes_products_added_to_the_database(): void
    {
        $categoryId = DB::table('kategori')->where('nama_kategori', 'Kopi')->value('id_kategori');
        DB::table('produk')->insert([
            'id_kategori' => $categoryId,
            'nama_produk' => 'Kopi Uji Database',
            'deskripsi' => 'Produk uji.',
            'harga' => 21000,
            'stok' => 4,
            'gambar' => 'https://example.test/coffee.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('dashboard.index', ['page' => 'menu', 'q' => 'Uji Database']))
            ->assertOk()
            ->assertSee('Kopi Uji Database')
            ->assertSee('Rp 21.000');
    }

    public function test_supplied_food_products_use_local_images_and_appear_in_the_menu(): void
    {
        $products = [
            'Banana Bread' => ['harga' => 22000, 'gambar' => 'resources/img/banana-bread.png'],
            'Club Sandwich' => ['harga' => 35000, 'gambar' => 'resources/img/club-sandwich.png'],
            'Chocolate Croissant' => ['harga' => 28000, 'gambar' => 'resources/img/chocolate-croissant.png'],
        ];

        foreach ($products as $name => $attributes) {
            $this->assertDatabaseHas('produk', [
                'nama_produk' => $name,
                ...$attributes,
            ]);
        }

        $this->get(route('dashboard.index', ['page' => 'menu', 'q' => 'Club Sandwich']))
            ->assertOk()
            ->assertSee('Club Sandwich')
            ->assertSee('club-sandwich-', false);

        $this->get(route('dashboard.index', ['page' => 'menu', 'q' => 'Chocolate Croissant']))
            ->assertOk()
            ->assertSee('Chocolate Croissant')
            ->assertSee('chocolate-croissant-', false);
    }

    public function test_menu_php_legacy_url_redirects_to_index_page_menu(): void
    {
        $this->get(route('menu.compat'))
            ->assertRedirect(route('dashboard.index', ['page' => 'menu']).'#menu');
    }
}
