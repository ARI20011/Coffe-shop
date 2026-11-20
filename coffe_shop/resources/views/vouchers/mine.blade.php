@extends('layouts.app')

@section('title', 'Voucher Saya')

@section('content')
<main class="voucher-page">
    <header class="voucher-heading">
        <div>
            <p class="eyebrow">Koleksi hadiahmu</p>
            <h1>Voucher <em>saya.</em></h1>
            <p>Voucher yang dibeli, diklaim dari misi, atau ditukar dengan kode promo.</p>
        </div>
        <a class="button button--brown" href="{{ route('vouchers.index') }}">Kembali ke Promo</a>
    </header>

    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif

    <nav class="voucher-tabs" aria-label="Halaman voucher">
        <a href="{{ route('vouchers.index') }}">Misi &amp; Kupon</a>
        <a class="is-active" href="{{ route('vouchers.mine') }}">Voucher Saya ({{ $vouchers->count() }})</a>
    </nav>

    @if ($vouchers->isNotEmpty())
        <section class="my-voucher-grid" aria-label="Koleksi voucher">
            @foreach ($vouchers as $voucher)
                <article class="my-voucher-card">
                    <div class="my-voucher-card__value">
                        @if ($voucher->tipe_diskon === 'persen')
                            {{ $voucher->nilai_diskon }}<small>% OFF</small>
                        @elseif ($voucher->tipe_diskon === 'produk_gratis')
                            GRATIS<small>MENU</small>
                        @else
                            <small>POTONGAN</small>Rp {{ number_format($voucher->nilai_diskon, 0, ',', '.') }}
                        @endif
                    </div>
                    <div class="my-voucher-card__details">
                        <span @class(['my-voucher-status', 'my-voucher-status--used' => $voucher->status === 'digunakan'])>
                            {{ $voucher->status === 'digunakan' ? 'Sudah digunakan' : 'Siap digunakan' }}
                        </span>
                        <h2>{{ $voucher->nama_voucher }}</h2>
                        <p>{{ $voucher->deskripsi }}</p>
                        <small>Sumber: {{ ucfirst($voucher->sumber) }}</small>
                        @if ($voucher->status === 'tersedia')
                            <a href="{{ route('dashboard.index', ['page' => 'menu']) }}">Pilih saat checkout →</a>
                        @else
                            <span class="my-voucher-card__used-date">
                                Dipakai {{ $voucher->digunakan_pada ? \Illuminate\Support\Carbon::parse($voucher->digunakan_pada)->format('d M Y, H:i') : '' }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="voucher-empty-state">
            <span aria-hidden="true">✦</span>
            <h2>Belum ada voucher</h2>
            <p>Selesaikan misi, tukarkan kode promo, atau koleksi voucher dari katalog.</p>
            <a class="button button--brown" href="{{ route('vouchers.index') }}">Jelajahi voucher</a>
        </section>
    @endif
</main>
@endsection
