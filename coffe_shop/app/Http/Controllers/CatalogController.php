<?php

namespace App\Http\Controllers;

use App\Support\MenuCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly MenuCatalog $menuCatalog) {}

    public function index(Request $request): View
    {
        $menu = $this->menuCatalog->all();

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
