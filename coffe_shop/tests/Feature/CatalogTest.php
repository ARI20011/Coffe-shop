<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogTest extends TestCase
{
    public function test_catalog_page_displays_menu_and_stock_status(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Popular Brews');
        $response->assertSee('Kopi yang diracik');
        $response->assertSee('Kenapa memilih kami');
        $response->assertSee('Dapatkan diskon 30%');
        $response->assertSee('Rp 18.000');
        $response->assertSee('Stok menipis');
        $response->assertSee('Habis');
        $response->assertSee('Tambah');
    }

    public function test_catalog_category_filter_works(): void
    {
        $response = $this->get(route('catalog.index', ['kategori' => 'Kopi']));

        $response->assertOk();
        $response->assertSee('Kopi Susu Kala');
        $response->assertDontSee('Matcha Aira');
    }
}
