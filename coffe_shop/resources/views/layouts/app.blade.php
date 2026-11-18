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
    <header class="site-header">
        <div class="site-header__inner">
            <a href="{{ route('catalog.index') }}" class="brand" aria-label="Kopi Kala Aira, beranda">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>ARTISAN ROASTERY</small></span>
            </a>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="#home">Home</a>
                <a href="#menu">Menu</a>
                <a href="#about">Tentang Kami</a>
                <a href="#promo">Promo Spesial</a>
            </nav>
            <a class="header-contact" href="#footer">Kontak <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    @yield('content')
</body>
</html>
