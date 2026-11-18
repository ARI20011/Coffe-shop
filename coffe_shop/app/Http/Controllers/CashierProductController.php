<?php

namespace App\Http\Controllers;

use App\Support\SalesPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierProductController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $product = $this->validatedProduct($request);
        $now = now();

        DB::table('produk')->insert([
            ...$this->productColumns($product),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->dashboard()->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        abort_unless(DB::table('produk')->where('id_produk', $productId)->exists(), 404);

        $product = $this->validatedProduct($request);
        DB::table('produk')->where('id_produk', $productId)->update([
            ...$this->productColumns($product),
            'updated_at' => now(),
        ]);

        return $this->dashboard()->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(int $productId): RedirectResponse
    {
        abort_unless(DB::table('produk')->where('id_produk', $productId)->exists(), 404);

        DB::table('produk')->where('id_produk', $productId)->delete();

        return $this->dashboard()->with('status', 'Produk berhasil dihapus.');
    }

    /**
     * @return array{nama_produk: string, kategori: ?string, deskripsi: ?string, harga: int, stok: int, gambar: ?string}
     */
    private function validatedProduct(Request $request): array
    {
        return $request->validate([
            'nama_produk' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'harga' => ['required', 'integer', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'gambar' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    /**
     * @param  array{nama_produk: string, kategori: ?string, deskripsi: ?string, harga: int, stok: int, gambar: ?string}  $product
     * @return array<string, mixed>
     */
    private function productColumns(array $product): array
    {
        $category = trim((string) ($product['kategori'] ?? ''));
        $categoryId = null;

        if ($category !== '') {
            $categoryId = DB::table('kategori')->where('nama_kategori', $category)->value('id_kategori');
            if ($categoryId === null) {
                $categoryId = DB::table('kategori')->insertGetId([
                    'nama_kategori' => $category,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id_kategori');
            }
        }

        return [
            'id_kategori' => $categoryId,
            'nama_produk' => $product['nama_produk'],
            'deskripsi' => $product['deskripsi'] ?? null,
            'harga' => $product['harga'],
            'stok' => $product['stok'],
            'gambar' => $product['gambar'] ?? null,
        ];
    }

    private function dashboard(): RedirectResponse
    {
        $period = request('periode', 'bulan');
        if (! array_key_exists($period, SalesPeriod::OPTIONS)) {
            $period = 'bulan';
        }

        return redirect()->route('dashboard.index', ['page' => 'kasir', 'periode' => $period]);
    }
}
