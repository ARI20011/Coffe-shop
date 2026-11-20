<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#48291b">
    <title>@yield('title', 'Beranda') · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    @php($activePage = request()->query('page', 'home'))
    <header class="site-header">
        <div class="site-header__inner">
            <a href="{{ route('dashboard.index', ['page' => 'home']) }}" class="brand" aria-label="Kopi Kala Aira, beranda">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>ARTISAN ROASTERY</small></span>
            </a>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="{{ route('dashboard.index', ['page' => 'home']) }}" @if ($activePage === 'home') aria-current="page" @endif>Home</a>
                <a href="{{ route('dashboard.index', ['page' => 'menu']) }}" @if ($activePage === 'menu') aria-current="page" @endif>Menu</a>
                <a href="{{ route('about.index') }}" @if (request()->routeIs('about.index')) aria-current="page" @endif>Tentang Kami</a>
                <a href="{{ route('vouchers.index') }}" @if (request()->routeIs('vouchers.*')) aria-current="page" @endif>Voucher</a>
            </nav>
            <div class="header-actions">
                <a class="header-cart" href="{{ route('cart.index') }}" aria-label="Keranjang, {{ array_sum(session('cart', [])) }} item">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.4L22 8H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1.3" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.3" fill="currentColor"/>
                    </svg>
                    <span>Keranjang</span>
                    <span class="header-cart__count">{{ array_sum(session('cart', [])) }}</span>
                </a>
                <a class="header-profile" href="{{ route('profile.show') }}" aria-label="Profil {{ auth()->user()?->name ?? 'Guest' }}">
                    <span class="header-profile__avatar" aria-hidden="true">
                        {{ auth()->user() ? mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) : 'G' }}
                    </span>
                    <span class="header-profile__name">{{ auth()->user()?->name ?? 'Guest' }}</span>
                </a>
                <a class="header-contact" href="{{ route('dashboard.index', ['page' => 'home']) }}#footer">Kontak <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </header>

    @yield('content')
</body>
</html>
