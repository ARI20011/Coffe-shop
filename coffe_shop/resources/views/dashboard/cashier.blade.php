@extends('layouts.staff')

@section('title', 'Dashboard kasir')

@section('content')
<section class="staff-page-heading staff-page-heading--hero">
    <div>
        <p class="staff-eyebrow">Ringkasan operasional</p>
        <h1>Dashboard kasir</h1>
        <p>Statistik penjualan dan akses cepat ke operasional kedai.</p>
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
</section>

<section class="staff-metrics" aria-label="Ringkasan penjualan">
    <article class="staff-metric">
        <span>Pesanan berhasil</span>
        <strong>{{ number_format($orderCount, 0, ',', '.') }}</strong>
        <small>{{ $periodOptions[$period] }}</small>
    </article>
    <article class="staff-metric">
        <span>Omzet Saldo Coffee Shop</span>
        <strong>Rp {{ number_format($walletRevenue, 0, ',', '.') }}</strong>
        <small>{{ $periodOptions[$period] }}</small>
    </article>
    <article class="staff-metric">
        <span>Omzet pembayaran lain</span>
        <strong>Rp {{ number_format($otherRevenue, 0, ',', '.') }}</strong>
        <small>{{ $periodOptions[$period] }}</small>
    </article>
    <article class="staff-metric">
        <span>Produk populer</span>
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
</section>

<section class="staff-panel" aria-labelledby="sales-chart-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Analisis pembelian</p>
            <h2 id="sales-chart-title">Produk terjual</h2>
        </div>
        <a class="staff-button staff-button--outline" href="{{ route('cashier.transactions.report', ['periode' => $period]) }}">Unduh laporan Excel (CSV)</a>
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
</section>

<section class="staff-quick-links" aria-label="Operasional kasir">
    <a class="staff-quick-link" href="{{ route('cashier.products.index') }}">
        <span aria-hidden="true">▤</span>
        <span><strong>Kelola produk</strong><small>Tambah menu, edit, dan atur stok</small></span>
        <span aria-hidden="true">→</span>
    </a>
    <a class="staff-quick-link" href="{{ route('cashier.transactions.index') }}">
        <span aria-hidden="true">▣</span>
        <span><strong>Kelola transaksi</strong><small>Pisahkan pesanan dine-in dan take-away</small></span>
        <span aria-hidden="true">→</span>
    </a>
</section>
@endsection
