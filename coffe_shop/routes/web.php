<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdminStockController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CashierProductController;
use App\Http\Controllers\CashierTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('catalog.index');
Route::get('/about.php', [AboutController::class, 'index'])->name('about.index');

Route::middleware('guest')->group(function () {
    Route::get('/login.php', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login.php', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register.php', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register.php', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login-admin.php', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/login-admin.php', [AuthController::class, 'adminLogin'])->name('admin.login.store');
    Route::get('/lupa-password.php', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/lupa-password.php', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::get('/profile.php', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/admin-profile.php', [ProfileController::class, 'staff'])
    ->middleware(['auth', 'role:admin,kasir'])
    ->name('staff.profile');
Route::get('/keranjang.php', [CartController::class, 'index'])->name('cart.index');
Route::get('/menu.php', fn () => redirect()->to(route('dashboard.index', ['page' => 'menu']).'#menu'))
    ->name('menu.compat');
Route::get('/index.php', [DashboardController::class, 'index'])->name('dashboard.index');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:pelanggan'])->group(function () {
    Route::post('/keranjang/tambah', [CartController::class, 'store'])->name('cart.store');
    Route::post('/keranjang/perbarui', [CartController::class, 'update'])->name('cart.update');
    Route::post('/keranjang/tipe', [CartController::class, 'setOrderMode'])->name('cart.mode');
    Route::get('/checkout.php', [CartController::class, 'showCheckout'])->name('checkout.show');
    Route::post('/checkout.php', [CartController::class, 'checkout'])->name('checkout.store');
    Route::post('/keranjang/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::get('/top-up.php', [TopUpController::class, 'index'])->name('topup.index');
    Route::post('/top-up.php', [TopUpController::class, 'store'])->name('topup.store');
    Route::get('/voucher.php', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchersaya.php', [VoucherController::class, 'mine'])->name('vouchers.mine');
    Route::get('/vochersaya.php', [VoucherController::class, 'mine'])->name('vouchers.mine.compat');
    Route::post('/voucher/tukar', [VoucherController::class, 'redeem'])->name('vouchers.redeem');
    Route::post('/voucher/tugas/{task}', [VoucherController::class, 'claimTask'])
        ->whereIn('task', ['topup', 'orders', 'referrals'])
        ->name('vouchers.tasks.claim');
    Route::post('/voucher/{voucherId}/beli', [VoucherController::class, 'purchase'])
        ->whereNumber('voucherId')
        ->name('vouchers.purchase');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/pengguna/{userId}', [AdminUserController::class, 'update'])->whereNumber('userId')->name('users.update');
    Route::delete('/pengguna/{userId}', [AdminUserController::class, 'destroy'])->whereNumber('userId')->name('users.destroy');
    Route::post('/pengguna/{userId}/buka-blokir', [AdminUserController::class, 'unlock'])->whereNumber('userId')->name('users.unlock');

    Route::get('/stok', [AdminStockController::class, 'index'])->name('stock.index');
    Route::put('/stok/{productId}', [AdminStockController::class, 'update'])->whereNumber('productId')->name('stock.update');
    Route::post('/stok/bahan', [AdminStockController::class, 'storeIngredient'])->name('stock.ingredients.store');
    Route::put('/stok/bahan/{ingredientId}', [AdminStockController::class, 'updateIngredient'])->whereNumber('ingredientId')->name('stock.ingredients.update');
    Route::delete('/stok/bahan/{ingredientId}', [AdminStockController::class, 'destroyIngredient'])->whereNumber('ingredientId')->name('stock.ingredients.destroy');
    Route::get('/stok/laporan.csv', [AdminStockController::class, 'report'])->name('stock.report');
});

Route::middleware(['auth', 'role:admin,kasir'])->group(function () {
    Route::get('/produk.php', [CashierProductController::class, 'index'])->name('cashier.products.index');
    Route::get('/transaksi.php', [CashierTransactionController::class, 'index'])->name('cashier.transactions.index');
    Route::post('/kasir/produk', [CashierProductController::class, 'store'])->name('cashier.products.store');
    Route::put('/kasir/produk/{productId}', [CashierProductController::class, 'update'])
        ->whereNumber('productId')
        ->name('cashier.products.update');
    Route::delete('/kasir/produk/{productId}', [CashierProductController::class, 'destroy'])
        ->whereNumber('productId')
        ->name('cashier.products.destroy');
    Route::put('/kasir/transaksi/{transactionId}/status', [CashierTransactionController::class, 'updateStatus'])
        ->whereNumber('transactionId')
        ->name('cashier.transactions.status');
    Route::put('/kasir/meja/{tableId}/status', [CashierTransactionController::class, 'updateTableStatus'])
        ->whereNumber('tableId')
        ->name('cashier.tables.status');
    Route::get('/kasir/laporan.csv', [CashierTransactionController::class, 'report'])
        ->name('cashier.transactions.report');
});
