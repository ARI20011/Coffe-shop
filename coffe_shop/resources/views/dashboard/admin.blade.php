@extends('layouts.staff')

@section('title', 'Dashboard admin')

@section('content')
<section class="staff-page-heading staff-page-heading--hero">
    <div>
        <p class="staff-eyebrow">Portal admin</p>
        <h1>Halo, {{ $user->username ?: $user->name }}.</h1>
        <p>Kelola pengguna, katalog, stok, dan transaksi kedai dari satu tempat.</p>
    </div>
</section>

<section class="staff-metrics" aria-label="Ringkasan operasional">
    <article class="staff-metric"><span>Pelanggan</span><strong>{{ number_format($customerCount, 0, ',', '.') }}</strong><small>Akun pelanggan terdaftar</small></article>
    <article class="staff-metric"><span>Produk</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong><small>Produk di katalog</small></article>
    <article class="staff-metric"><span>Stok menipis</span><strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong><small>Stok 5 atau kurang</small></article>
    <article class="staff-metric"><span>Pesanan berjalan</span><strong>{{ number_format($openOrderCount, 0, ',', '.') }}</strong><small>Status Paid atau Shipped</small></article>
</section>

<section class="staff-quick-links" aria-label="Navigasi utama admin">
    <a class="staff-quick-link" href="{{ route('cashier.products.index') }}">
        <span aria-hidden="true">▤</span>
        <span><strong>CRUD Produk</strong><small>Cari, tambah, edit, dan hapus produk</small></span>
        <span aria-hidden="true">→</span>
    </a>
    <a class="staff-quick-link" href="{{ route('admin.users.index') }}">
        <span aria-hidden="true">♙</span>
        <span><strong>CRUD Pengguna</strong><small>Kelola akun pelanggan dan staf</small></span>
        <span aria-hidden="true">→</span>
    </a>
    <a class="staff-quick-link" href="{{ route('cashier.transactions.index') }}">
        <span aria-hidden="true">▣</span>
        <span><strong>Kelola Transaksi</strong><small>Ubah status pesanan dan unduh laporan</small></span>
        <span aria-hidden="true">→</span>
    </a>
    <a class="staff-quick-link" href="{{ route('admin.stock.index') }}">
        <span aria-hidden="true">▥</span>
        <span><strong>Kelola Stok</strong><small>Sesuaikan stok, lihat riwayat, dan ekspor laporan</small></span>
        <span aria-hidden="true">→</span>
    </a>
</section>
@endsection
