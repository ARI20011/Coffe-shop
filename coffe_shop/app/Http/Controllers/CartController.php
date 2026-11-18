<?php

namespace App\Http\Controllers;

use App\Support\MenuCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly MenuCatalog $menuCatalog) {}

    public function index(Request $request): View
    {
        $cart = $request->user() ? $request->session()->get('cart', []) : [];
        $items = [];

        foreach ($cart as $menuId => $quantity) {
            $item = $this->menuCatalog->find((int) $menuId);
            if ($item === null) {
                continue;
            }

            $item['jumlah'] = (int) $quantity;
            $item['subtotal'] = $item['harga'] * $item['jumlah'];
            $items[] = $item;
        }

        return view('cart.index', [
            'items' => $items,
            'total' => array_sum(array_column($items, 'subtotal')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', Rule::in(array_column($this->menuCatalog->all(), 'id_menu'))],
        ], [
            'menu_id.required' => 'Menu yang dipilih tidak valid.',
            'menu_id.integer' => 'Menu yang dipilih tidak valid.',
            'menu_id.in' => 'Menu yang dipilih tidak tersedia.',
        ]);

        $menuId = (int) $validated['menu_id'];
        $item = $this->menuCatalog->find($menuId);
        $cart = $request->session()->get('cart', []);
        $quantity = (int) ($cart[$menuId] ?? 0);

        if ($item === null || $item['stok_realtime'] <= $quantity) {
            return back()->withErrors(['cart' => 'Stok menu tersebut sudah habis.']);
        }

        $cart[$menuId] = $quantity + 1;
        $request->session()->put('cart', $cart);

        return back()->with('status', $item['nama_menu'].' ditambahkan ke keranjang.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', Rule::in(array_column($this->menuCatalog->all(), 'id_menu'))],
            'action' => ['required', Rule::in(['increase', 'decrease', 'remove'])],
        ]);

        $menuId = (int) $validated['menu_id'];
        $cart = $request->session()->get('cart', []);
        $quantity = (int) ($cart[$menuId] ?? 0);

        if ($validated['action'] === 'remove' || ($validated['action'] === 'decrease' && $quantity <= 1)) {
            unset($cart[$menuId]);
        } elseif ($validated['action'] === 'increase') {
            $item = $this->menuCatalog->find($menuId);
            if ($item === null || $quantity >= $item['stok_realtime']) {
                return back()->withErrors(['cart' => 'Jumlah pesanan sudah mencapai stok yang tersedia.']);
            }

            $cart[$menuId] = $quantity + 1;
        } elseif ($quantity > 1) {
            $cart[$menuId] = $quantity - 1;
        }

        $request->session()->put('cart', $cart);

        return back()->with('status', 'Keranjang berhasil diperbarui.');
    }
}
