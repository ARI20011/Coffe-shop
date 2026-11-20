<?php

namespace App\Http\Controllers;

use App\Support\StockLedger;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStockController extends Controller
{
    private const SALES_STATUSES = ['paid', 'shipped', 'completed'];

    public function __construct(private readonly StockLedger $stockLedger) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);
        $sold = $this->soldQuantityQuery();
        $products = DB::table('produk')
            ->leftJoin('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori')
            ->leftJoinSub($sold, 'sold', 'sold.id_produk', '=', 'produk.id_produk')
            ->select('produk.*', 'kategori.nama_kategori', DB::raw('COALESCE(sold.quantity, 0) as quantity_sold'));

        if (($filters['q'] ?? '') !== '') {
            $products->where('produk.nama_produk', 'like', '%'.$filters['q'].'%');
        }
        if (($filters['kategori'] ?? '') !== '') {
            $products->where('kategori.nama_kategori', $filters['kategori']);
        }

        return view('dashboard.stock', [
            'products' => $products->orderBy('produk.nama_produk')->paginate(15)->withQueryString(),
            'categories' => DB::table('kategori')->orderBy('nama_kategori')->pluck('nama_kategori'),
            'filters' => $filters,
            'history' => DB::table('riwayat_stok')
                ->leftJoin('users', 'users.id', '=', 'riwayat_stok.id_admin')
                ->orderByDesc('riwayat_stok.tanggal_catat')
                ->limit(20)
                ->get([
                    'riwayat_stok.*',
                    'users.name as actor_name',
                ]),
        ]);
    }

    public function update(Request $request, int $productId): RedirectResponse
    {
        $validated = $request->validate([
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        DB::transaction(function () use ($request, $productId, $validated): void {
            $product = DB::table('produk')
                ->where('id_produk', $productId)
                ->lockForUpdate()
                ->first(['id_produk', 'nama_produk', 'stok']);
            abort_if($product === null, 404);

            $newStock = (int) $validated['stok'];
            $difference = $newStock - (int) $product->stok;
            if ($difference === 0) {
                return;
            }

            DB::table('produk')->where('id_produk', $productId)->update([
                'stok' => $newStock,
                'updated_at' => now(),
            ]);
            $this->stockLedger->record(
                (int) $product->id_produk,
                $product->nama_produk,
                $difference > 0 ? 'penyesuaian_masuk' : 'penyesuaian_keluar',
                abs($difference),
                (int) $request->user()->getKey(),
            );
        });

        return redirect()->route('admin.stock.index')->with('status', 'Stok produk berhasil diperbarui.');
    }

    public function report(): StreamedResponse
    {
        $products = DB::table('produk')
            ->leftJoin('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori')
            ->leftJoinSub($this->soldQuantityQuery(), 'sold', 'sold.id_produk', '=', 'produk.id_produk')
            ->select(
                'produk.id_produk',
                'produk.nama_produk',
                'kategori.nama_kategori',
                'produk.stok',
                DB::raw('COALESCE(sold.quantity, 0) as quantity_sold'),
            )
            ->orderBy('produk.nama_produk');

        return response()->streamDownload(function () use ($products): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka output laporan stok.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID Produk', 'Nama Produk', 'Kategori', 'Stok Saat Ini', 'Total Terjual']);
            foreach ($products->cursor() as $product) {
                fputcsv($output, [
                    $product->id_produk,
                    $this->spreadsheetSafe($product->nama_produk),
                    $this->spreadsheetSafe($product->nama_kategori ?? ''),
                    $product->stok,
                    $product->quantity_sold,
                ]);
            }
            fclose($output);
        }, 'laporan-stok-'.now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function soldQuantityQuery(): Builder
    {
        return DB::table('detail_transaksi as details')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'details.id_transaksi')
            ->whereIn('transaksi.status_pesanan', self::SALES_STATUSES)
            ->whereNotNull('details.id_produk')
            ->select('details.id_produk', DB::raw('SUM(details.jumlah) as quantity'))
            ->groupBy('details.id_produk');
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
