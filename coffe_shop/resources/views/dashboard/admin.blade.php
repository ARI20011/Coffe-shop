<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#051F20">
    <title>Dashboard Admin · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="staff-dashboard">
    <header class="staff-header">
        <a class="staff-brand" href="{{ route('dashboard.index', ['page' => 'admin']) }}">Kopi Kala Aira <span>ADMIN</span></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="staff-logout" type="submit">Keluar</button>
        </form>
    </header>
    <main class="staff-main">
        <section class="staff-hero">
            <p class="staff-eyebrow">Portal admin</p>
            <h1>Halo, {{ $user->username }}.</h1>
            <p>Login admin sudah aktif. Dashboard kasir tersedia; fitur khusus admin dapat ditambahkan berikutnya.</p>
        </section>
        <section class="staff-panel">
            <h2>Akses dashboard kasir</h2>
            <p>Akun admin belum diberi akses ke fitur operasional kasir.</p>
        </section>
    </main>
</body>
</html>
