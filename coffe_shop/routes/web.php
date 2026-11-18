<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');

Route::middleware('guest')->group(function () {
    Route::get('/login.php', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login.php', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register.php', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register.php', [AuthController::class, 'register'])->name('register.store');
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
Route::get('/keranjang.php', [CartController::class, 'index'])->name('cart.index');

Route::middleware('auth')->group(function () {
    Route::post('/keranjang/tambah', [CartController::class, 'store'])->name('cart.store');
    Route::post('/keranjang/perbarui', [CartController::class, 'update'])->name('cart.update');
    Route::post('/keranjang/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
