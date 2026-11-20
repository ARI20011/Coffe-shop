<?php

namespace App\Http\Controllers;

use App\Support\MenuCatalog;
use App\Support\TableAvailability;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly MenuCatalog $menuCatalog,
        private readonly TableAvailability $tableAvailability,
    ) {}

    public function index(Request $request): View
    {
        $page = $request->query('page', 'home');
        $columns = in_array((int) $request->query('columns', 2), [2, 3], true)
            ? (int) $request->query('columns', 2)
            : 2;
        $search = trim($request->string('q')->toString());
        $kategori = $request->string('kategori')->toString();
        $salesByProduct = DB::table('detail_transaksi as details')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'details.id_transaksi')
            ->whereIn('transaksi.status_pesanan', ['paid', 'shipped', 'completed'])
            ->whereNotNull('details.id_produk')
            ->select('details.id_produk', DB::raw('SUM(details.jumlah) as quantity'))
            ->groupBy('details.id_produk')
            ->orderByDesc('quantity')
            ->pluck('quantity', 'id_produk');
        $allMenu = collect($this->menuCatalog->all())
            ->map(static fn (array $item): array => [
                ...$item,
                'jumlah_terjual' => (int) ($salesByProduct[$item['id_menu']] ?? 0),
            ])
            ->sortByDesc('jumlah_terjual')
            ->values()
            ->all();
        $categories = array_values(array_unique(array_column($allMenu, 'kategori')));
        $bestSellerIds = collect($allMenu)
            ->filter(static fn (array $item): bool => $item['jumlah_terjual'] > 0)
            ->take(4)
            ->pluck('id_menu')
            ->all();
        $bestSellerItems = collect($allMenu)
            ->filter(static fn (array $item): bool => $item['jumlah_terjual'] > 0)
            ->take(4)
            ->values();
        $menu = $page === 'menu' || $kategori !== ''
            ? $allMenu
            : array_slice($allMenu, 0, 4);

        if ($kategori !== '' && $kategori !== 'Semua') {
            $menu = array_values(array_filter($menu, fn (array $item): bool => $item['kategori'] === $kategori));
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $menu = array_values(array_filter($menu, fn (array $item): bool => str_contains(
                mb_strtolower($item['nama_menu'].' '.$item['kategori']),
                $needle
            )));
        }

        if ($page === 'menu') {
            $perPage = $columns * $columns;
            $currentPage = LengthAwarePaginator::resolveCurrentPage('menu_page');
            $menu = new LengthAwarePaginator(
                array_slice($menu, ($currentPage - 1) * $perPage, $perPage),
                count($menu),
                $perPage,
                $currentPage,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => 'menu_page',
                    'query' => $request->query(),
                ],
            );
        } else {
            $menu = collect($menu);
        }

        $orderType = $request->session()->get('order_type', 'takeaway');
        if (! in_array($orderType, ['takeaway', 'dine_in'], true)) {
            $orderType = 'takeaway';
        }

        $orderItems = [];
        $orderTotal = 0;
        if ($request->user()) {
            $cartKey = $orderType === 'dine_in' ? 'dine_in_cart' : 'cart';
            $orderCart = $request->session()->get($cartKey, []);
            foreach ($orderCart as $menuId => $quantity) {
                $item = $this->menuCatalog->find((int) $menuId);
                if ($item === null || ! is_numeric($quantity) || (int) $quantity < 1) {
                    continue;
                }

                $item['jumlah'] = (int) $quantity;
                $item['subtotal'] = $item['harga'] * $item['jumlah'];
                $orderTotal += $item['subtotal'];
                $orderItems[] = $item;
            }
        }

        $promo = DB::table('vouchers')->where('code', 'KALA30')->first();
        $promoClaims = $promo === null
            ? 0
            : DB::table('voucher_pelanggan')
                ->where('id_voucher', $promo->id_voucher)
                ->where('sumber', 'kode')
                ->count();
        $showPromo = $promo !== null
            && ($promo->batas_klaim === null || $promoClaims < (int) $promo->batas_klaim)
            && ($request->user() === null || ! DB::table('voucher_pelanggan')
                ->where('id_pelanggan', $request->user()->getKey())
                ->where('id_voucher', $promo->id_voucher)
                ->exists());
        $promoClaimsLeft = $promo === null
            ? 0
            : max(0, (int) ($promo->batas_klaim ?? 0) - $promoClaims);

        return view($page === 'menu' ? 'catalog.menu' : 'catalog.index', [
            'page' => $page,
            'columns' => $columns,
            'menu' => $menu,
            'categories' => $categories,
            'kategoriAktif' => $kategori ?: 'Semua',
            'search' => $search,
            'bestSellerIds' => $bestSellerIds,
            'bestSellerItems' => $bestSellerItems,
            'showPromo' => $showPromo,
            'promoClaimsLeft' => $promoClaimsLeft,
            'orderType' => $orderType,
            'orderItems' => $orderItems,
            'orderTotal' => $orderTotal,
            'availableTables' => $this->tableAvailability->all(),
            'selectedTableId' => $request->session()->get('dine_in_table'),
        ]);
    }
}
