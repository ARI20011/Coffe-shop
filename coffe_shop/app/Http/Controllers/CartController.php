<?php

namespace App\Http\Controllers;

use App\Support\MenuCatalog;
use App\Support\StockLedger;
use App\Support\TableAvailability;
use App\Support\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    private const PAYMENT_METHODS = [
        'saldo_coffee_shop' => 'Saldo Coffee Shop',
    ];

    private const TOP_UP_ONLY_METHODS = [
        'dana' => 'DANA',
        'qris' => 'QRIS',
        'gopay' => 'GoPay',
    ];

    public function __construct(
        private readonly MenuCatalog $menuCatalog,
        private readonly TableAvailability $tableAvailability,
        private readonly VoucherService $vouchers,
        private readonly StockLedger $stockLedger,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (in_array($request->user()?->role, ['admin', 'kasir'], true)) {
            return redirect()->route('dashboard.index', ['page' => $request->user()->role]);
        }

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

    public function showCheckout(Request $request): View|RedirectResponse
    {
        $orderType = $request->query('order_type', $request->session()->get('order_type', 'takeaway'));
        if (! in_array($orderType, ['takeaway', 'dine_in'], true)) {
            abort(404);
        }

        $tableId = $orderType === 'dine_in'
            ? (int) $request->query('table_id', $request->session()->get('dine_in_table', 0))
            : null;
        $cartKey = $orderType === 'dine_in' ? 'dine_in_cart' : 'cart';
        $cart = $request->session()->get($cartKey, []);
        if (! is_array($cart) || $cart === []) {
            return redirect()
                ->route('dashboard.index', ['page' => 'menu'])
                ->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        if ($orderType === 'dine_in' && ($tableId < 1 || ! $this->tableAvailability->isAvailable($tableId))) {
            return redirect()
                ->route('dashboard.index', ['page' => 'menu'])
                ->withErrors(['table_id' => 'Meja tersebut sudah tidak tersedia. Pilih meja lain.']);
        }

        $items = [];
        foreach ($cart as $menuId => $quantity) {
            $item = $this->menuCatalog->find((int) $menuId);
            if ($item === null || ! is_numeric($quantity) || (int) $quantity < 1) {
                return redirect()
                    ->route('dashboard.index', ['page' => 'menu'])
                    ->withErrors(['cart' => 'Ada menu dengan jumlah yang tidak valid.']);
            }

            $item['jumlah'] = (int) $quantity;
            $item['subtotal'] = $item['harga'] * $item['jumlah'];
            $items[] = $item;
        }

        $table = $tableId === null
            ? null
            : DB::table('meja')->where('id_meja', $tableId)->first(['id_meja', 'nomor_meja']);
        $eligibleVouchers = $this->vouchers->eligibleForCheckout(
            (int) $request->user()->getKey(),
            array_map(static fn (array $item): array => [
                'id_produk' => $item['id_menu'],
                'harga' => $item['harga'],
                'jumlah' => $item['jumlah'],
                'nama_produk' => $item['nama_menu'],
            ], $items),
            $orderType,
        );
        $selectedVoucherId = (int) $request->query('voucher_id', 0);
        $selectedVoucher = $eligibleVouchers->firstWhere('id_voucher_pelanggan', $selectedVoucherId);
        $subtotal = array_sum(array_column($items, 'subtotal'));
        $discount = $selectedVoucher?->potongan ?? 0;

        return view('checkout.index', [
            'orderType' => $orderType,
            'table' => $table,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
            'eligibleVouchers' => $eligibleVouchers,
            'selectedVoucherId' => $selectedVoucher?->id_voucher_pelanggan,
            'saldo' => (int) $request->user()->saldo,
            'paymentMethods' => self::PAYMENT_METHODS,
            'topUpOnlyMethods' => self::TOP_UP_ONLY_METHODS,
            'scheduledAt' => $request->query('arrival_at') ?? $request->query('pickup_at'),
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
        $orderType = $request->session()->get('order_type', 'takeaway');
        $cartKey = $orderType === 'dine_in' ? 'dine_in_cart' : 'cart';
        $cart = $request->session()->get($cartKey, []);
        $quantity = (int) ($cart[$menuId] ?? 0);

        if ($orderType === 'dine_in') {
            $tableId = (int) $request->session()->get('dine_in_table', 0);
            if ($tableId < 1 || ! $this->tableAvailability->isAvailable($tableId)) {
                return back()->withErrors(['table_id' => 'Pilih meja yang masih tersedia sebelum menambah pesanan dine-in.']);
            }
        }

        if ($item === null || $item['stok_realtime'] <= $quantity) {
            return back()->withErrors(['cart' => 'Stok menu tersebut sudah habis.']);
        }

        $cart[$menuId] = $quantity + 1;
        $request->session()->put($cartKey, $cart);

        return back()->with('status', $item['nama_menu'].' ditambahkan ke keranjang.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', Rule::in(array_column($this->menuCatalog->all(), 'id_menu'))],
            'action' => ['required', Rule::in(['increase', 'decrease', 'remove'])],
            'order_type' => ['nullable', Rule::in(['takeaway', 'dine_in'])],
        ]);

        $menuId = (int) $validated['menu_id'];
        $orderType = $validated['order_type'] ?? 'takeaway';
        $cartKey = $orderType === 'dine_in' ? 'dine_in_cart' : 'cart';
        $cart = $request->session()->get($cartKey, []);
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

        $request->session()->put($cartKey, $cart);

        return back()->with('status', 'Keranjang berhasil diperbarui.');
    }

    public function setOrderMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['takeaway', 'dine_in'])],
            'table_id' => ['nullable', 'integer', 'exists:meja,id_meja'],
        ]);

        if ($validated['order_type'] === 'dine_in' && ! empty($validated['table_id'])) {
            $tableId = (int) $validated['table_id'];
            if (! $this->tableAvailability->isAvailable($tableId)) {
                return back()->withErrors(['table_id' => 'Meja tersebut sudah dipesan. Silakan pilih meja lain.']);
            }

            $request->session()->put('dine_in_table', $tableId);
        }

        $request->session()->put('order_type', $validated['order_type']);

        return back()->with('status', $validated['order_type'] === 'dine_in'
            ? 'Mode dine-in dipilih.'
            : 'Mode take away dipilih.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['takeaway', 'dine_in'])],
            'table_id' => ['required_if:order_type,dine_in', 'nullable', 'integer', 'exists:meja,id_meja'],
            'arrival_at' => ['required_if:order_type,dine_in', 'nullable', 'date', 'after:now'],
            'pickup_at' => ['required_if:order_type,takeaway', 'nullable', 'date', 'after:now'],
            'metode_pembayaran' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'voucher_id' => ['nullable', 'integer'],
        ], [
            'metode_pembayaran.in' => 'Checkout hanya dapat dibayar menggunakan Saldo Coffee Shop. Gunakan DANA, QRIS, atau GoPay di halaman top up.',
            'arrival_at.required_if' => 'Waktu tiba wajib dipilih untuk pesanan dine-in.',
            'arrival_at.after' => 'Waktu tiba harus setelah waktu sekarang.',
            'pickup_at.required_if' => 'Waktu pengambilan wajib dipilih untuk pesanan take away.',
            'pickup_at.after' => 'Waktu pengambilan harus setelah waktu sekarang.',
        ]);
        $orderType = $validated['order_type'];
        $tableId = $orderType === 'dine_in' ? (int) ($validated['table_id'] ?? 0) : null;
        $cartKey = $orderType === 'dine_in' ? 'dine_in_cart' : 'cart';
        $cart = $request->session()->get($cartKey, []);

        if (! is_array($cart) || $cart === []) {
            return back()->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        foreach ($cart as $menuId => $quantity) {
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($this->menuCatalog->find((int) $menuId) === null || $quantity === false || $quantity < 1) {
                return back()->withErrors(['cart' => 'Ada menu dengan jumlah atau stok yang tidak valid.']);
            }
        }

        if ($orderType === 'dine_in' && $tableId < 1) {
            return back()->withErrors(['table_id' => 'Pilih meja yang tersedia sebelum checkout dine-in.']);
        }

        $result = DB::transaction(function () use ($request, $cart, $orderType, $tableId, $validated): array {
            if ($orderType === 'dine_in') {
                $table = DB::table('meja')
                    ->where('id_meja', $tableId)
                    ->lockForUpdate()
                    ->first();

                if ($table === null || strtolower($table->status_meja) !== 'tersedia' || ! $this->tableAvailability->isAvailable($tableId)) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Meja tersebut baru saja dipesan. Silakan pilih meja lain.',
                    ]);
                }
            }

            $items = [];
            foreach ($cart as $menuId => $quantity) {
                $product = DB::table('produk')
                    ->where('id_produk', (int) $menuId)
                    ->lockForUpdate()
                    ->first();

                if ($product === null || (int) $quantity > (int) $product->stok) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok produk berubah. Periksa kembali jumlah pesanan.',
                    ]);
                }

                $quantity = (int) $quantity;
                $items[] = [
                    'id_produk' => (int) $product->id_produk,
                    'nama_menu' => $product->nama_produk,
                    'nama_produk' => $product->nama_produk,
                    'harga' => (int) $product->harga,
                    'jumlah' => $quantity,
                    'subtotal' => (int) $product->harga * $quantity,
                ];
            }

            $subtotal = array_sum(array_column($items, 'subtotal'));
            $voucherUser = null;
            $discount = 0;
            if (! empty($validated['voucher_id'] ?? null)) {
                $voucherUser = DB::table('voucher_pelanggan')
                    ->join('vouchers', 'vouchers.id_voucher', '=', 'voucher_pelanggan.id_voucher')
                    ->where('voucher_pelanggan.id_voucher_pelanggan', (int) $validated['voucher_id'])
                    ->where('voucher_pelanggan.id_pelanggan', $request->user()->getKey())
                    ->where('voucher_pelanggan.status', 'tersedia')
                    ->where('vouchers.aktif', true)
                    ->lockForUpdate()
                    ->first([
                        'voucher_pelanggan.id_voucher_pelanggan',
                        'vouchers.*',
                    ]);

                if ($voucherUser === null) {
                    throw ValidationException::withMessages(['voucher_id' => 'Voucher ini tidak tersedia atau sudah digunakan.']);
                }

                $quote = $this->vouchers->quote($voucherUser, $items, $orderType);
                if ($quote === null) {
                    throw ValidationException::withMessages(['voucher_id' => 'Syarat voucher belum terpenuhi untuk pesanan ini.']);
                }

                $discount = $quote['discount'];
            }
            $total = max(0, $subtotal - $discount);
            $customer = DB::table('users')
                ->where('id', $request->user()->getKey())
                ->lockForUpdate()
                ->first(['id', 'saldo']);
            abort_if($customer === null, 404);

            $paymentMethod = $validated['metode_pembayaran'];
            $paymentSucceeded = (int) $customer->saldo >= $total;
            $now = now();
            $transactionId = DB::table('transaksi')->insertGetId([
                'id_pelanggan' => $request->user()->getKey(),
                'id_meja' => $tableId,
                'tanggal_transaksi' => $now,
                'waktu_tiba' => $orderType === 'dine_in' ? Carbon::parse($validated['arrival_at'])->toDateTimeString() : null,
                'waktu_pengambilan' => $orderType === 'takeaway' ? Carbon::parse($validated['pickup_at'])->toDateTimeString() : null,
                'total_harga' => $total,
                'metode_pembayaran' => $paymentMethod,
                'status_pembayaran' => $paymentSucceeded ? 'berhasil' : 'gagal',
                'status_pesanan' => $paymentSucceeded ? 'paid' : 'cancelled',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_transaksi');

            foreach ($items as $item) {
                DB::table('detail_transaksi')->insert([
                    'id_transaksi' => $transactionId,
                    'id_produk' => $item['id_produk'],
                    'nama_produk' => $item['nama_menu'],
                    'harga_satuan' => $item['harga'],
                    'jumlah' => $item['jumlah'],
                    'subtotal_harga' => $item['subtotal'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($paymentSucceeded) {
                foreach ($items as $item) {
                    DB::table('produk')
                        ->where('id_produk', $item['id_produk'])
                        ->decrement('stok', $item['jumlah'], ['updated_at' => $now]);
                    $this->stockLedger->record(
                        $item['id_produk'],
                        $item['nama_menu'],
                        'pembelian',
                        $item['jumlah'],
                        null,
                    );
                }

                if ($paymentMethod === 'saldo_coffee_shop') {
                    DB::table('users')
                        ->where('id', $customer->id)
                        ->decrement('saldo', $total, ['updated_at' => $now]);
                }

                if ($voucherUser !== null) {
                    DB::table('voucher_pelanggan')
                        ->where('id_voucher_pelanggan', $voucherUser->id_voucher_pelanggan)
                        ->update([
                            'status' => 'digunakan',
                            'id_transaksi' => $transactionId,
                            'digunakan_pada' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }

            if ($paymentSucceeded && $orderType === 'dine_in') {
                DB::table('meja')
                    ->where('id_meja', $tableId)
                    ->update(['status_meja' => 'dipesan', 'updated_at' => $now]);
            }

            return ['id' => $transactionId, 'paid' => $paymentSucceeded];
        });

        if (! $result['paid']) {
            return redirect()
                ->route('checkout.show', [
                    'order_type' => $orderType,
                    'table_id' => $tableId,
                    'arrival_at' => $validated['arrival_at'] ?? null,
                    'pickup_at' => $validated['pickup_at'] ?? null,
                    'voucher_id' => $validated['voucher_id'] ?? null,
                ])
                ->withErrors(['payment' => 'Saldo Coffee Shop tidak cukup. Pembayaran gagal dan pesanan dibatalkan. Silakan top up saldo terlebih dahulu.']);
        }

        $request->session()->forget($cartKey);
        if ($orderType === 'dine_in') {
            $request->session()->forget('dine_in_table');
        }

        return redirect()
            ->route('profile.show')
            ->with('status', "Checkout berhasil. Nomor pesanan #{$result['id']} tersimpan di riwayat profil.");
    }
}
