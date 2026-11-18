<?php

namespace App\Http\Controllers;

use App\Support\SalesPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SALES_STATUSES = ['paid', 'shipped', 'completed'];

    private const STATUS_TRANSITIONS = [
        'menunggu' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    private const STATUS_LABELS = [
        'menunggu' => 'Menunggu pembayaran',
        'paid' => 'Paid',
        'shipped' => 'Shipped',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['admin', 'kasir'], true), 403);

        $page = $user->role;
        if ($request->query('page') === null) {
            return redirect()->route('dashboard.index', ['page' => $page]);
        }

        abort_unless($request->query('page') === $page, 403);

        if ($page === 'admin') {
            return view('dashboard.admin', ['user' => $user]);
        }

        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(array_keys(SalesPeriod::OPTIONS))],
        ]);
        $period = $validated['periode'] ?? 'bulan';
        [$start, $end] = SalesPeriod::range($period);

        $orders = DB::table('transaksi')->whereBetween('tanggal_transaksi', [$start, $end]);
        $completedOrders = (clone $orders)->whereIn('status_pesanan', self::SALES_STATUSES);
        $orderCount = (clone $completedOrders)->count();
        $revenue = (clone $completedOrders)->sum('total_harga');

        $itemsSold = DB::table('detail_transaksi as details')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'details.id_transaksi')
            ->whereBetween('transaksi.tanggal_transaksi', [$start, $end])
            ->whereIn('transaksi.status_pesanan', self::SALES_STATUSES)
            ->select('details.nama_produk', DB::raw('SUM(details.jumlah) as quantity'))
            ->groupBy('details.nama_produk')
            ->get();

        $salesByName = $itemsSold->keyBy('nama_produk');
        $productNames = DB::table('produk')
            ->pluck('nama_produk')
            ->merge($itemsSold->pluck('nama_produk'))
            ->filter()
            ->unique()
            ->values();

        $productStats = $productNames
            ->map(fn (string $name): array => [
                'nama_produk' => $name,
                'terjual' => (int) ($salesByName->get($name)->quantity ?? 0),
            ])
            ->sortByDesc('terjual')
            ->values();

        $popularItem = $productStats->first(fn (array $item): bool => $item['terjual'] > 0);
        $leastPurchasedItem = $productStats->sortBy('terjual')->first();
        $maxSold = max(1, (int) $productStats->max('terjual'));
        $chartItems = $productStats->take(6)->map(fn (array $item): array => [
            ...$item,
            'persentase' => (int) round(($item['terjual'] / $maxSold) * 100),
        ]);

        $products = DB::table('produk')
            ->leftJoin('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori')
            ->select('produk.*', 'kategori.nama_kategori')
            ->orderBy('produk.nama_produk')
            ->get();

        $transactions = DB::table('transaksi')
            ->join('users', 'users.id', '=', 'transaksi.id_pelanggan')
            ->whereBetween('transaksi.tanggal_transaksi', [$start, $end])
            ->select('transaksi.*', 'users.username')
            ->orderByDesc('transaksi.tanggal_transaksi')
            ->limit(50)
            ->get();

        return view('dashboard.cashier', [
            'user' => $user,
            'period' => $period,
            'periodOptions' => SalesPeriod::OPTIONS,
            'orderCount' => $orderCount,
            'revenue' => $revenue,
            'popularItem' => $popularItem,
            'leastPurchasedItem' => $leastPurchasedItem,
            'chartItems' => $chartItems,
            'products' => $products,
            'transactions' => $transactions,
            'statusLabels' => self::STATUS_LABELS,
            'statusTransitions' => self::STATUS_TRANSITIONS,
        ]);
    }
}
