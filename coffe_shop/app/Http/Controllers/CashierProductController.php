<?php

namespace App\Http\Controllers;

use App\Support\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashierProductController extends Controller
{
    public function __construct(private readonly StockLedger $stockLedger) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);
        $products = DB::table('produk')
            ->leftJoin('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori');
        if (($filters['q'] ?? '') !== '') {
            $products->where('produk.nama_produk', 'like', '%'.$filters['q'].'%');
        }
        if (($filters['kategori'] ?? '') !== '') {
            $products->where('kategori.nama_kategori', $filters['kategori']);
        }

        return view('dashboard.products', [
            'products' => $products
                ->select('produk.*', 'kategori.nama_kategori')
                ->orderBy('produk.nama_produk')
                ->paginate(10, ['produk.*', 'kategori.nama_kategori'], 'products_page')
                ->withQueryString(),
            'categories' => DB::table('kategori')->orderBy('nama_kategori')->pluck('nama_kategori'),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = $this->validatedProduct($request);

        DB::transaction(function () use ($request, $product): void {
            $now = now();
            $productId = DB::table('produk')->insertGetId([
                ...$this->productColumns($product),
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_produk');
            if ($product['stok'] > 0) {
                $this->stockLedger->record(
                    (int) $productId,
                    $product['nama_produk'],
                    'stok_awal',
                    $product['stok'],
                    (int) $request->user()->getKey(),
                );
            }
        });

        return redirect()->route('cashier.products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        $product = $this->validatedProduct($request);
        DB::transaction(function () use ($request, $productId, $product): void {
            $existing = DB::table('produk')
                ->where('id_produk', $productId)
                ->lockForUpdate()
                ->first(['nama_produk', 'stok']);
            abort_if($existing === null, 404);

            DB::table('produk')->where('id_produk', $productId)->update([
                ...$this->productColumns($product),
                'updated_at' => now(),
            ]);
            $difference = $product['stok'] - (int) $existing->stok;
            if ($difference !== 0) {
                $this->stockLedger->record(
                    $productId,
                    $product['nama_produk'],
                    $difference > 0 ? 'penyesuaian_masuk' : 'penyesuaian_keluar',
                    abs($difference),
                    (int) $request->user()->getKey(),
                );
            }
        });

        return redirect()->route('cashier.products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(int $productId): RedirectResponse
    {
        abort_unless(DB::table('produk')->where('id_produk', $productId)->exists(), 404);

        if (DB::table('detail_transaksi')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'detail_transaksi.id_transaksi')
            ->where('detail_transaksi.id_produk', $productId)
            ->whereIn('transaksi.status_pesanan', ['paid', 'shipped'])
            ->exists()) {
            return back()->withErrors([
                'product' => 'Produk tidak dapat dihapus selama masih ada pesanan aktif. Selesaikan atau batalkan pesanan dahulu.',
            ]);
        }

        DB::table('produk')->where('id_produk', $productId)->delete();

        return redirect()->route('cashier.products.index')->with('status', 'Produk berhasil dihapus.');
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
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],
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
}
