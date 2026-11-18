<?php

namespace App\Http\Controllers;

use App\Support\SalesPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashierTransactionController extends Controller
{
    private const STATUS_TRANSITIONS = [
        'menunggu' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function updateStatus(Request $request, int $transactionId): RedirectResponse
    {
        $validated = $request->validate([
            'status_pesanan' => ['required', Rule::in(['paid', 'shipped', 'completed', 'cancelled'])],
        ]);

        $changed = DB::transaction(function () use ($transactionId, $validated): bool {
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
                'transaksi.status_pesanan',
            )
            ->orderByDesc('transaksi.tanggal_transaksi');

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka output laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID Pesanan', 'Tanggal', 'Username', 'Total', 'Status']);

            foreach ($transactions->cursor() as $transaction) {
                fputcsv($output, [
                    $transaction->id_transaksi,
                    $transaction->tanggal_transaksi,
                    $this->spreadsheetSafe($transaction->username),
                    $transaction->total_harga,
                    $transaction->status_pesanan,
                ]);
            }

            fclose($output);
        }, "laporan-pesanan-{$period}-".now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
