<?php

namespace App\Http\Controllers;

use App\Support\SalesPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SALES_STATUSES = ['paid', 'shipped', 'completed'];

    public function index(Request $request): View|RedirectResponse
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        $user = $request->user();
        $requestedPage = $request->query('page', 'home');

        if ($user !== null
            && in_array($user->role, ['admin', 'kasir'], true)
            && in_array($requestedPage, ['home', 'menu'], true)) {
            return redirect()->route('dashboard.index', ['page' => $user->role]);
        }

        if (in_array($requestedPage, ['home', 'menu'], true)) {
            return app(CatalogController::class)->index($request);
        }

        if (! in_array($requestedPage, ['admin', 'kasir'], true)) {
            abort(404);
        }

        if ($user === null) {
            return redirect()->route('admin.login');
        }

        $page = $user->role;
        abort_unless(in_array($page, ['admin', 'kasir'], true) && $requestedPage === $page, 403);

        if ($page === 'admin') {
            return view('dashboard.admin', [
                'user' => $user,
                'customerCount' => DB::table('users')->where('role', 'pelanggan')->count(),
                'productCount' => DB::table('produk')->count(),
                'lowStockCount' => DB::table('produk')->where('stok', '<=', 5)->count(),
                'openOrderCount' => DB::table('transaksi')->whereIn('status_pesanan', ['paid', 'shipped'])->count(),
            ]);
        }

        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(array_keys(SalesPeriod::OPTIONS))],
        ]);
        $period = $validated['periode'] ?? 'bulan';
        [$start, $end] = SalesPeriod::range($period);

        $orders = DB::table('transaksi')->whereBetween('tanggal_transaksi', [$start, $end]);
        $completedOrders = (clone $orders)->whereIn('status_pesanan', self::SALES_STATUSES);
        $orderCount = (clone $completedOrders)->count();
        $walletRevenue = (clone $completedOrders)
            ->where('metode_pembayaran', 'saldo_coffee_shop')
            ->sum('total_harga');
        $otherRevenue = (clone $completedOrders)
            ->where(function ($query): void {
                $query->whereNull('metode_pembayaran')
                    ->orWhere('metode_pembayaran', '!=', 'saldo_coffee_shop');
            })
            ->sum('total_harga');

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

        return view('dashboard.cashier', [
            'user' => $user,
            'period' => $period,
            'periodOptions' => SalesPeriod::OPTIONS,
            'orderCount' => $orderCount,
            'walletRevenue' => $walletRevenue,
            'otherRevenue' => $otherRevenue,
            'popularItem' => $popularItem,
            'leastPurchasedItem' => $leastPurchasedItem,
            'chartItems' => $chartItems,
        ]);
    }
}
