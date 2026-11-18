@extends('layouts.app')

@section('title', 'Keranjang')

@section('content')
<main class="account-page">
    <div class="account-heading">
        <p class="eyebrow">Pesananmu</p>
        <h1>Keranjang <em>kopi.</em></h1>
        <p>Periksa pilihanmu sebelum kembali menikmati hari.</p>
    </div>

    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif
    @error('cart')
        <p class="form-notice form-notice--error" role="alert">{{ $message }}</p>
    @enderror

    @guest
        <section class="account-card guest-card">
            <span class="guest-card__icon" aria-hidden="true">♧</span>
            <h2>Masuk untuk mulai memesan</h2>
            <p>Keranjang hanya tersedia setelah kamu masuk ke akun.</p>
            <a class="button button--brown" href="{{ route('login', ['next' => 'home']) }}">Masuk ke akun <span aria-hidden="true">→</span></a>
        </section>
    @else
        @if (count($items) > 0)
            <section class="cart-page-list" aria-label="Daftar pesanan">
                @foreach ($items as $item)
                    <article class="cart-page-item">
                        <div class="cart-page-item__photo">
                            <img
                                src="{{ $item['id_menu'] === 1 ? Vite::asset('resources/img/coffee-cup.png') : $item['foto_url'] }}"
                                alt="{{ $item['nama_menu'] }}"
                            >
                        </div>
                        <div class="cart-page-item__details">
                            <p class="menu-card__category">{{ $item['kategori'] }}</p>
                            <h2>{{ $item['nama_menu'] }}</h2>
                            <p>Rp {{ number_format($item['harga'], 0, ',', '.') }} per item</p>
                        </div>
                        <div class="cart-page-item__controls">
                            <form method="POST" action="{{ route('cart.update') }}">
                                @csrf
                                <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                                <input type="hidden" name="action" value="decrease">
                                <button type="submit" class="quantity-button" aria-label="Kurangi {{ $item['nama_menu'] }}">−</button>
                            </form>
                            <span>{{ $item['jumlah'] }}</span>
                            <form method="POST" action="{{ route('cart.update') }}">
                                @csrf
                                <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                                <input type="hidden" name="action" value="increase">
                                <button type="submit" class="quantity-button" aria-label="Tambah {{ $item['nama_menu'] }}" @disabled($item['jumlah'] >= $item['stok_realtime'])>+</button>
                            </form>
                        </div>
                        <div class="cart-page-item__subtotal">
                            <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                            <form method="POST" action="{{ route('cart.update') }}">
                                @csrf
                                <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                                <input type="hidden" name="action" value="remove">
                                <button class="remove-button" type="submit">Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach

                <div class="cart-page-total">
                    <span>Total pesanan</span>
                    <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                </div>
                <a class="button button--brown cart-continue" href="{{ route('catalog.index') }}#menu">Lanjut belanja <span aria-hidden="true">→</span></a>
            </section>
        @else
            <section class="account-card guest-card">
                <span class="guest-card__icon" aria-hidden="true">♧</span>
                <h2>Keranjangmu masih kosong</h2>
                <p>Pilih minuman favoritmu dari menu, nanti pesananmu muncul di sini.</p>
                <a class="button button--brown" href="{{ route('catalog.index') }}#menu">Lihat menu <span aria-hidden="true">→</span></a>
            </section>
        @endif
    @endguest
</main>
@endsection
