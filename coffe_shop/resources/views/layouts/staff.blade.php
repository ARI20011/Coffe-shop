<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#051F20">
    <title>@yield('title', 'Dashboard staf') · Coffee Shop</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="staff-dashboard">
    <header class="staff-topbar">
        <div class="staff-topbar__actions">
            <a class="staff-profile-link" href="{{ route('staff.profile') }}">
                <span class="staff-profile-link__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span>{{ auth()->user()->username ?: auth()->user()->name }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Yakin ingin keluar dari dashboard?')">
                @csrf
                <button class="staff-logout" type="submit">Logout</button>
            </form>
        </div>
        <a class="staff-topbar__brand" href="{{ route('dashboard.index', ['page' => auth()->user()->role]) }}" aria-label="Coffee Shop staf">
            <span class="staff-topbar__mark" aria-hidden="true">C</span>
            <span><strong>COFFEE_SHOP</strong><small>STAFF PANEL</small></span>
        </a>
    </header>

    <div class="staff-shell">
        <aside class="staff-sidebar" aria-label="Navigasi staf">
            <p class="staff-sidebar__label">WORKSPACE</p>
            <nav>
                <a href="{{ route('dashboard.index', ['page' => auth()->user()->role]) }}"
                    @class(['is-active' => request()->routeIs('dashboard.index') && request()->query('page') === auth()->user()->role])>
                    <span aria-hidden="true">▦</span> Dashboard
                </a>
                <a href="{{ route('cashier.products.index') }}" @class(['is-active' => request()->routeIs('cashier.products.*')])>
                    <span aria-hidden="true">▤</span> Kelola produk
                </a>
                <a href="{{ route('cashier.transactions.index') }}" @class(['is-active' => request()->routeIs('cashier.transactions.*')])>
                    <span aria-hidden="true">▣</span> Kelola transaksi
                </a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')])>
                        <span aria-hidden="true">♙</span> Kelola pengguna
                    </a>
                    <a href="{{ route('admin.stock.index') }}" @class(['is-active' => request()->routeIs('admin.stock.*')])>
                        <span aria-hidden="true">▥</span> Kelola stok
                    </a>
                @endif
            </nav>
            <div class="staff-sidebar__footer">
                <span class="staff-sidebar__status" aria-hidden="true"></span>
                <span>Masuk sebagai {{ ucfirst(auth()->user()->role) }}</span>
            </div>
        </aside>

        <main class="staff-workspace">
            @if (session('status'))
                <p class="staff-notice staff-notice--success" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="staff-notice staff-notice--error" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-dialog-open]');
            if (opener) {
                document.getElementById(opener.dataset.dialogOpen)?.showModal();
            }

            const closer = event.target.closest('[data-dialog-close]');
            if (closer) {
                closer.closest('dialog')?.close();
            }

            if (event.target instanceof HTMLDialogElement) {
                event.target.close();
            }
        });
    </script>
</body>
</html>
