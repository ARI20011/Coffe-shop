<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#051F20">
    <title>Dashboard Kasir · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="staff-dashboard">
    <header class="staff-header">
        <a class="staff-brand" href="{{ route('dashboard.index', ['page' => 'kasir']) }}">Kopi Kala Aira <span>KASIR</span></a>
        <nav class="staff-nav" aria-label="Navigasi dashboard">
            <a href="#statistik">Statistik</a>
            <a href="#produk">Produk</a>
            <a href="#pesanan">Pesanan</a>
        </nav>
        <div class="staff-header__user">
            <span>{{ $user->username }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="staff-logout" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="staff-main">
        <section class="staff-hero">
            <p class="staff-eyebrow">Ringkasan operasional</p>
            <h1>Dashboard kasir</h1>
            <p>Kelola produk dan pesanan, lalu pantau menu yang paling sering dibeli.</p>
        </section>

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

        <section class="staff-panel" id="statistik">
            <div class="staff-section-heading">
                <div>
                    <p class="staff-eyebrow">Analisis pembelian</p>
                    <h2>Statistik penjualan</h2>
                    <p>Periode: {{ $periodOptions[$period] }}</p>
                </div>
                <form method="GET" action="{{ route('dashboard.index') }}" class="staff-period-form">
                    <input type="hidden" name="page" value="kasir">
                    <label for="periode">Rentang waktu</label>
                    <select id="periode" name="periode" onchange="this.form.submit()">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="staff-metrics">
                <article class="staff-metric">
                    <span>Pesanan terbayar</span>
                    <strong>{{ number_format($orderCount, 0, ',', '.') }}</strong>
                </article>
                <article class="staff-metric">
                    <span>Omzet</span>
                    <strong>Rp {{ number_format($revenue, 0, ',', '.') }}</strong>
                </article>
                <article class="staff-metric">
                    <span>Paling populer</span>
                    <strong>{{ $popularItem['nama_produk'] ?? 'Belum ada pembelian' }}</strong>
                    @if ($popularItem)
                        <small>{{ number_format($popularItem['terjual'], 0, ',', '.') }} item terjual</small>
                    @endif
                </article>
                <article class="staff-metric">
                    <span>Paling jarang dibeli</span>
                    <strong>{{ $leastPurchasedItem['nama_produk'] ?? 'Belum ada produk' }}</strong>
                    @if ($leastPurchasedItem)
                        <small>{{ number_format($leastPurchasedItem['terjual'], 0, ',', '.') }} item terjual</small>
                    @endif
                </article>
            </div>

            @if ($chartItems->isNotEmpty())
                <div class="staff-chart" aria-label="Jumlah item terjual">
                    @foreach ($chartItems as $item)
                        <div class="staff-chart__row">
                            <span>{{ $item['nama_produk'] }}</span>
                            <div class="staff-chart__track"><span style="width: {{ $item['persentase'] }}%"></span></div>
                            <strong>{{ $item['terjual'] }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="staff-empty">Belum ada data produk atau transaksi untuk periode ini.</p>
            @endif

            <a class="staff-button staff-button--outline" href="{{ route('cashier.transactions.report', ['periode' => $period]) }}">Unduh laporan CSV</a>
        </section>

        <section class="staff-panel" id="produk">
            <div class="staff-section-heading">
                <div>
                    <p class="staff-eyebrow">Menu kedai</p>
                    <h2>Kelola produk</h2>
                </div>
            </div>

            <form method="POST" action="{{ route('cashier.products.store') }}" class="staff-product-form">
                @csrf
                <input type="hidden" name="periode" value="{{ $period }}">
                <label>Nama produk<input name="nama_produk" value="{{ old('nama_produk') }}" maxlength="150" required></label>
                <label>Kategori<input name="kategori" value="{{ old('kategori') }}" maxlength="100" placeholder="Contoh: Kopi"></label>
                <label>Harga (Rp)<input name="harga" type="number" min="0" value="{{ old('harga') }}" required></label>
                <label>Stok<input name="stok" type="number" min="0" value="{{ old('stok') }}" required></label>
                <label class="staff-product-form__wide">Deskripsi<textarea name="deskripsi" rows="2" maxlength="5000">{{ old('deskripsi') }}</textarea></label>
                <label class="staff-product-form__wide">URL/path gambar<input name="gambar" value="{{ old('gambar') }}" maxlength="2048" placeholder="resources/img/... atau https://..."></label>
                <button class="staff-button" type="submit">Tambah produk</button>
            </form>

            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->nama_produk }}</strong>
                                    @if ($product->deskripsi)
                                        <small>{{ $product->deskripsi }}</small>
                                    @endif
                                </td>
                                <td>{{ $product->nama_kategori ?? '—' }}</td>
                                <td>Rp {{ number_format($product->harga, 0, ',', '.') }}</td>
                                <td>{{ number_format($product->stok, 0, ',', '.') }}</td>
                                <td class="staff-table__actions">
                                    <details class="staff-edit">
                                        <summary>Edit</summary>
                                        <form method="POST" action="{{ route('cashier.products.update', $product->id_produk) }}" class="staff-edit__form">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="periode" value="{{ $period }}">
                                            <label>Nama produk<input name="nama_produk" value="{{ $product->nama_produk }}" maxlength="150" required></label>
                                            <label>Kategori<input name="kategori" value="{{ $product->nama_kategori }}" maxlength="100"></label>
                                            <label>Harga (Rp)<input name="harga" type="number" min="0" value="{{ $product->harga }}" required></label>
                                            <label>Stok<input name="stok" type="number" min="0" value="{{ $product->stok }}" required></label>
                                            <label>Deskripsi<textarea name="deskripsi" rows="2" maxlength="5000">{{ $product->deskripsi }}</textarea></label>
                                            <label>URL/path gambar<input name="gambar" value="{{ $product->gambar }}" maxlength="2048"></label>
                                            <button class="staff-button" type="submit">Simpan perubahan</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('cashier.products.destroy', $product->id_produk) }}" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="periode" value="{{ $period }}">
                                        <button class="staff-button staff-button--danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="staff-empty">Belum ada produk. Tambahkan produk pertama di formulir atas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="staff-panel" id="pesanan">
            <div class="staff-section-heading">
                <div>
                    <p class="staff-eyebrow">Pesanan pelanggan</p>
                    <h2>Kelola transaksi</h2>
                    <p>Perubahan status mengikuti alur Menunggu → Paid → Shipped → Completed.</p>
                </div>
            </div>

            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr><th>Pesanan</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Ubah status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            @php
                                $nextStatuses = array_merge(
                                    [$transaction->status_pesanan],
                                    $statusTransitions[$transaction->status_pesanan] ?? [],
                                );
                            @endphp
                            <tr>
                                <td>#{{ $transaction->id_transaksi }}</td>
                                <td>{{ $transaction->username }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal_transaksi)->format('d M Y, H:i') }}</td>
                                <td>Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</td>
                                <td><span class="staff-status">{{ $statusLabels[$transaction->status_pesanan] ?? $transaction->status_pesanan }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('cashier.transactions.status', $transaction->id_transaksi) }}" class="staff-status-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="periode" value="{{ $period }}">
                                        <select name="status_pesanan" aria-label="Status pesanan #{{ $transaction->id_transaksi }}">
                                            @foreach ($nextStatuses as $nextStatus)
                                                <option value="{{ $nextStatus }}">{{ $statusLabels[$nextStatus] ?? $nextStatus }}</option>
                                            @endforeach
                                        </select>
                                        @if (count($nextStatuses) > 1)
                                            <button class="staff-button" type="submit">Simpan</button>
                                        @else
                                            <button class="staff-button" type="submit" disabled>Selesai</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="staff-empty">Belum ada pesanan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
