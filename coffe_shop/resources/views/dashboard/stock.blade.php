@extends('layouts.staff')

@section('title', 'Kelola stok')

@section('content')
<section class="staff-page-heading">
    <div>
        <p class="staff-eyebrow">Inventaris</p>
        <h1>Kelola stok</h1>
        <p>Stok berkurang otomatis saat checkout berhasil dan kembali saat pesanan dibatalkan.</p>
    </div>
    <a class="staff-button staff-button--outline" href="{{ route('admin.stock.report') }}">Unduh laporan stok CSV</a>
</section>

<section class="staff-panel" aria-labelledby="stock-list-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Kontrol persediaan</p>
            <h2 id="stock-list-title">Stok produk dan total pembelian</h2>
        </div>
    </div>

    <form class="staff-filter-form" method="GET" action="{{ route('admin.stock.index') }}">
        <label class="sr-only" for="stock-search">Cari produk</label>
        <input id="stock-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama produk">
        <label class="sr-only" for="stock-category">Kategori</label>
        <select id="stock-category" name="kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(($filters['kategori'] ?? '') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <button class="staff-button" type="submit">Cari produk</button>
    </form>

    <div class="staff-table-wrap">
        <table class="staff-table">
            <thead><tr><th>Produk</th><th>Kategori</th><th>Terjual</th><th>Stok tersedia</th><th>Perbarui stok</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><strong>{{ $product->nama_produk }}</strong></td>
                        <td>{{ $product->nama_kategori ?? 'Lainnya' }}</td>
                        <td>{{ number_format($product->quantity_sold, 0, ',', '.') }}</td>
                        <td>
                            <span @class(['staff-stock-level', 'staff-stock-level--low' => $product->stok <= 5, 'staff-stock-level--empty' => (int) $product->stok === 0])>
                                {{ number_format($product->stok, 0, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.stock.update', $product->id_produk) }}" class="staff-stock-form">
                                @csrf
                                @method('PUT')
                                <label class="sr-only" for="stock-{{ $product->id_produk }}">Stok {{ $product->nama_produk }}</label>
                                <input id="stock-{{ $product->id_produk }}" name="stok" type="number" min="0" max="1000000" value="{{ $product->stok }}" required>
                                <button class="staff-button staff-button--outline" type="submit">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Tidak ada produk yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="staff-pagination">{{ $products->links('pagination.coffee') }}</div>
</section>

<section class="staff-panel" aria-labelledby="stock-history-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Audit persediaan</p>
            <h2 id="stock-history-title">Riwayat perubahan stok terbaru</h2>
        </div>
    </div>
    <div class="staff-table-wrap">
        <table class="staff-table">
            <thead><tr><th>Tanggal</th><th>Produk</th><th>Pergerakan</th><th>Jumlah</th><th>Dicatat oleh</th></tr></thead>
            <tbody>
                @forelse ($history as $movement)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($movement->tanggal_catat)->format('d M Y, H:i') }}</td>
                        <td>{{ $movement->nama_bahan }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($movement->tipe_pergerakan)) }}</td>
                        <td>{{ number_format($movement->jumlah, 0, ',', '.') }} {{ $movement->satuan }}</td>
                        <td>{{ $movement->actor_name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Belum ada riwayat perubahan stok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
