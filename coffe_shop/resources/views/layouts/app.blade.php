<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kopi Kala Aira') · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-stone-50 text-stone-950">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('catalog.index') }}" class="font-bold tracking-tight">Kopi Kala Aira</a>
            <a href="{{ route('catalog.index', ['kategori' => 'Semua']) }}" class="text-sm font-semibold text-stone-600">Menu Pelanggan</a>
        </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
        @yield('content')
    </div>
</body>
</html>
