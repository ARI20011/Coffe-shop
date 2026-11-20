@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<main class="catalog-menu-page">
    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif
    @error('cart')
        <p class="form-notice form-notice--error" role="alert">{{ $message }}</p>
    @enderror

    <section class="menu-section catalog-menu-content" id="menu" aria-labelledby="catalog-menu-title">
        <div class="catalog-menu-heading">
            <div>
                <p class="eyebrow">Dibuat segar setiap hari</p>
                <h2 id="catalog-menu-title">Menu <em>pilihan</em></h2>
            </div>
            <span class="catalog-menu-count">{{ $menu->total() }} menu tersedia</span>
        </div>

        <form class="menu-search" method="GET" action="{{ route('dashboard.index') }}" role="search">
            <input type="hidden" name="page" value="menu">
            @if ($kategoriAktif !== 'Semua')
                <input type="hidden" name="kategori" value="{{ $kategoriAktif }}">
            @endif
            <label class="sr-only" for="menu-search-input">Cari menu</label>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/>
                <path d="m16 16 4.3 4.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
            <input id="menu-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari espresso, cold brew, croissant, matcha..." autocomplete="off">
            <button type="submit">Cari menu</button>
        </form>

        <section class="menu-best-sellers" aria-labelledby="menu-best-sellers-title">
            <div class="menu-best-sellers__heading">
                <div>
                    <p class="eyebrow">Favorit pelanggan</p>
                    <h2 id="menu-best-sellers-title">Cari menu terlaris?</h2>
                </div>
                <span>Pesanan berhasil dibayar</span>
            </div>
            @if ($bestSellerItems->isNotEmpty())
                <div class="menu-best-sellers__items">
                    @foreach ($bestSellerItems as $index => $item)
                        <a href="{{ route('dashboard.index', ['page' => 'menu']) }}#menu-produk-{{ $item['id_menu'] }}">
                            <span class="menu-best-sellers__rank">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span><strong>{{ $item['nama_menu'] }}</strong><small>{{ number_format($item['jumlah_terjual']) }} terjual</small></span>
                            <span class="menu-best-sellers__price">Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="menu-best-sellers__empty">Menu terlaris akan tampil setelah ada pesanan yang berhasil dibayar.</p>
            @endif
        </section>

        <nav class="category-nav catalog-category-nav" aria-label="Filter kategori menu">
            <a href="{{ route('dashboard.index', ['page' => 'menu', 'q' => $search ?: null]) }}#menu"
                @class(['category-link', 'is-active' => $kategoriAktif === 'Semua'])>Semua menu</a>
            @foreach ($categories as $category)
                <a href="{{ route('dashboard.index', ['page' => 'menu', 'kategori' => $category, 'q' => $search ?: null]) }}#menu"
                    @class(['category-link', 'is-active' => $kategoriAktif === $category])>{{ $category }}</a>
            @endforeach
        </nav>

        <div class="catalog-grid-controls" aria-label="Jumlah kolom produk">
            <span>Tampilan produk</span>
            @foreach ([2 => '2 × 2', 3 => '3 × 3'] as $columnCount => $label)
                <a href="{{ route('dashboard.index', ['page' => 'menu', 'columns' => $columnCount, 'kategori' => $kategoriAktif === 'Semua' ? null : $kategoriAktif, 'q' => $search ?: null]) }}"
                    @class(['is-active' => $columns === $columnCount])
                    aria-current="{{ $columns === $columnCount ? 'true' : 'false' }}">{{ $label }}</a>
            @endforeach
        </div>

        <p class="catalog-menu-result" aria-live="polite">
            @if ($search !== '')
            Hasil pencarian “{{ $search }}” · {{ $menu->total() }} menu
            @else
            Menampilkan {{ $menu->total() }} racikan pilihan
            @endif
        </p>

        <div class="catalog-menu-layout">
            <div class="catalog-menu-products">
                <div class="catalog-menu-grid" style="--menu-columns: {{ $columns }}">
                    @include('catalog.partials.menu-grid')
                </div>
                <div class="catalog-pagination">{{ $menu->links('pagination.coffee') }}</div>
            </div>
            @include('catalog.partials.order-panel')
        </div>
    </section>

</main>
@endsection
