<?php

namespace App\Http\Controllers;

use App\Support\SalesPeriod;
use App\Support\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashierTransactionController extends Controller
{
    private const STATUS_LABELS = [
        'menunggu' => 'Menunggu pembayaran',
        'paid' => 'Berhasil dibayar',
        'shipped' => 'Siap diambil',
        'completed' => 'Selesai',
        'cancelled' => 'Gagal / dibatalkan',
    ];

    private const STATUS_TRANSITIONS = [
        'menunggu' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'completed', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly StockLedger $stockLedger) {}

    public function index(Request $request): View
    {
        $transactionQuery = static fn () => DB::table('transaksi')
            ->join('users', 'users.id', '=', 'transaksi.id_pelanggan')
            ->leftJoin('meja', 'meja.id_meja', '=', 'transaksi.id_meja')
            ->select(
                'transaksi.*',
                'users.name as customer_name',
                'users.username',
                'meja.nomor_meja',
                'meja.status_meja'
            );
        $columns = [
            'transaksi.*',
            'users.name as customer_name',
            'users.username',
            'meja.nomor_meja',
            'meja.status_meja',
        ];
        $dineInTransactions = $transactionQuery()
            ->whereNotNull('transaksi.id_meja')
            ->orderByDesc('transaksi.tanggal_transaksi')
            ->paginate(10, $columns, 'dine_page')
            ->withQueryString();
        $takeawayTransactions = $transactionQuery()
            ->whereNull('transaksi.id_meja')
            ->orderByDesc('transaksi.tanggal_transaksi')
            ->paginate(10, $columns, 'takeaway_page')
            ->withQueryString();
        $transactions = $dineInTransactions->getCollection()
            ->concat($takeawayTransactions->getCollection());

        $detailsByTransaction = $transactions->isEmpty()
            ? collect()
            : DB::table('detail_transaksi')
                ->whereIn('id_transaksi', $transactions->pluck('id_transaksi'))
                ->get()
                ->groupBy('id_transaksi');

        return view('dashboard.transactions', [
            'dineInTransactions' => $dineInTransactions,
            'takeawayTransactions' => $takeawayTransactions,
            'detailsByTransaction' => $detailsByTransaction,
            'statusLabels' => self::STATUS_LABELS,
            'statusTransitions' => self::STATUS_TRANSITIONS,
            'tableStatuses' => [
                'dipesan' => 'Dipesan',
                'digunakan' => 'Meja dipakai',
                'tersedia' => 'Tersedia',
            ],
        ]);
    }

    public function updateTableStatus(Request $request, int $tableId): RedirectResponse
    {
        $validated = $request->validate([
            'status_meja' => ['required', Rule::in(['tersedia', 'dipesan', 'digunakan'])],
        ]);

        DB::transaction(function () use ($tableId, $validated): void {
            $table = DB::table('meja')->where('id_meja', $tableId)->lockForUpdate()->first();
            abort_if($table === null, 404);

            DB::table('meja')->where('id_meja', $tableId)->update([
                'status_meja' => $validated['status_meja'],
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Status meja berhasil diperbarui.');
    }

    public function updateStatus(Request $request, int $transactionId): RedirectResponse
    {
        $validated = $request->validate([
            'status_pesanan' => ['required', Rule::in(['paid', 'shipped', 'completed', 'cancelled'])],
        ]);

        $changed = DB::transaction(function () use ($request, $transactionId, $validated): bool {
            $transaction = DB::table('transaksi')
                ->where('id_transaksi', $transactionId)
                ->lockForUpdate()
                ->first();

            abort_if($transaction === null, 404);

            if (! in_array($validated['status_pesanan'], self::STATUS_TRANSITIONS[$transaction->status_pesanan] ?? [], true)) {
                return false;
            }

            DB::table('transaksi')
                ->where('id_transaksi', $transactionId)
                ->update([
                    'status_pesanan' => $validated['status_pesanan'],
                    'updated_at' => now(),
                ]);

            if ($validated['status_pesanan'] === 'cancelled' && $transaction->status_pembayaran === 'berhasil') {
                $details = DB::table('detail_transaksi')
                    ->where('id_transaksi', $transactionId)
                    ->whereNotNull('id_produk')
                    ->get(['id_produk', 'nama_produk', 'jumlah']);

                foreach ($details as $detail) {
                    $changed = DB::table('produk')
                        ->where('id_produk', $detail->id_produk)
                        ->increment('stok', (int) $detail->jumlah, ['updated_at' => now()]);
                    if ($changed < 1) {
                        continue;
                    }

                    $this->stockLedger->record(
                        (int) $detail->id_produk,
                        $detail->nama_produk,
                        'pembatalan',
                        (int) $detail->jumlah,
                        (int) $request->user()->getKey(),
                    );
                }

                if ($transaction->metode_pembayaran === 'saldo_coffee_shop') {
                    DB::table('users')
                        ->where('id', $transaction->id_pelanggan)
                        ->increment('saldo', (int) $transaction->total_harga, ['updated_at' => now()]);
                }

                DB::table('transaksi')
                    ->where('id_transaksi', $transactionId)
                    ->update(['status_pembayaran' => 'dikembalikan', 'updated_at' => now()]);
            }

            return true;
        });

        if (! $changed) {
            return back()->withErrors([
                'status_pesanan' => 'Perubahan status tersebut tidak sesuai urutan pesanan.',
            ]);
        }

        return back()->with('status', 'Status pesanan berhasil diperbarui.');
    }

    public function report(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(array_keys(SalesPeriod::OPTIONS))],
        ]);
        $period = $validated['periode'] ?? 'bulan';
        [$start, $end] = SalesPeriod::range($period);

        $transactions = DB::table('transaksi')
            ->join('users', 'users.id', '=', 'transaksi.id_pelanggan')
            ->whereBetween('transaksi.tanggal_transaksi', [$start, $end])
            ->select(
                'transaksi.id_transaksi',
                'transaksi.tanggal_transaksi',
                'users.username',
                'transaksi.total_harga',
                'transaksi.metode_pembayaran',
                'transaksi.status_pembayaran',
                'transaksi.status_pesanan',
            )
            ->orderByDesc('transaksi.tanggal_transaksi');

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka output laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID Pesanan', 'Tanggal', 'Username', 'Total', 'Metode Pembayaran', 'Status Pembayaran', 'Status Pesanan']);

            foreach ($transactions->cursor() as $transaction) {
                fputcsv($output, [
                    $transaction->id_transaksi,
                    $transaction->tanggal_transaksi,
                    $this->spreadsheetSafe($transaction->username),
                    $transaction->total_harga,
                    $transaction->metode_pembayaran ?? '',
                    $transaction->status_pembayaran ?? '',
                    $transaction->status_pesanan,
                ]);
            }

            fclose($output);
        }, "laporan-pesanan-{$period}-".now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function spreadsheetSafe(?string $value): string
    {
        $value ??= '';

        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
