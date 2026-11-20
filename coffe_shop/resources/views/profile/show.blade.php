@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<main class="account-page">
    <div class="account-heading">
        <p class="eyebrow">Akun pelanggan</p>
        <h1>Profil <em>kamu.</em></h1>
        <p>Kelola akun dan lihat informasi profilmu di sini.</p>
    </div>

    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif

    @guest
        <section class="account-card guest-card">
            <span class="guest-card__icon" aria-hidden="true">G</span>
            <p class="eyebrow">Mode guest</p>
            <h2>Kamu belum masuk.</h2>
            <p>Masuk atau buat akun untuk menyimpan keranjang dan melihat profilmu.</p>
            <div class="guest-card__actions">
                <a class="button button--brown" href="{{ route('login') }}">Masuk <span aria-hidden="true">→</span></a>
                <a class="button button--profile-outline" href="{{ route('register') }}">Buat akun</a>
            </div>
        </section>
    @else
        <section class="account-card profile-card">
            <div class="profile-card__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->username ?: $user->name, 0, 1)) }}</div>
            <div class="profile-card__info">
                <p class="eyebrow">Profil pelanggan</p>
                <h2>{{ $user->name }}</h2>
                <dl class="profile-details">
                    <div><dt>Username</dt><dd>{{ $user->username ?: $user->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                    <div><dt>Saldo Coffee Shop</dt><dd>Rp {{ number_format($user->saldo ?? 0, 0, ',', '.') }}</dd></div>
                    <div>
                        <dt>Login terakhir</dt>
                        <dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum pernah login' }}</dd>
                    </div>
                </dl>
                <span>Bergabung sejak {{ $user->created_at?->format('d M Y') ?? 'Tanggal tidak tersedia' }}</span>
            </div>
            <div class="profile-card__actions">
                @if ($user->role === 'pelanggan')
                    <a class="button button--brown" href="{{ route('topup.index') }}">Top up saldo</a>
                @endif
                <a class="button button--profile-outline" href="{{ route('cart.index') }}">Lihat keranjang</a>
            </div>
        </section>

        <section class="profile-history" aria-labelledby="profile-history-title">
            <div class="profile-history__heading">
                <p class="eyebrow">Pesanan tersimpan selama 30 hari</p>
                <h2 id="profile-history-title">Riwayat checkout</h2>
            </div>
            @forelse ($transactions as $transaction)
                <article class="profile-order">
                    <header class="profile-order__header">
                        <div>
                            <h3>Pesanan #{{ $transaction->id_transaksi }}</h3>
                            <time datetime="{{ \Illuminate\Support\Carbon::parse($transaction->tanggal_transaksi)->toAtomString() }}">
                                {{ \Illuminate\Support\Carbon::parse($transaction->tanggal_transaksi)->format('d M Y, H:i') }}
                            </time>
                        </div>
                        <span class="profile-order__status">{{ ucfirst($transaction->status_pesanan) }}</span>
                    </header>
                    <p class="profile-order__meta">
                        {{ $transaction->id_meja ? 'Dine-in' : 'Take-away' }}
                        · {{ strtoupper(str_replace('_', ' ', $transaction->metode_pembayaran ?? '—')) }}
                        · Pembayaran {{ ucfirst($transaction->status_pembayaran ?? 'menunggu') }}
                        @if ($transaction->id_meja)
                            · Tiba {{ $transaction->waktu_tiba ? \Illuminate\Support\Carbon::parse($transaction->waktu_tiba)->format('d M Y, H:i') : '—' }}
                        @else
                            · Ambil {{ $transaction->waktu_pengambilan ? \Illuminate\Support\Carbon::parse($transaction->waktu_pengambilan)->format('d M Y, H:i') : '—' }}
                        @endif
                    </p>
                    <ul>
                        @foreach ($detailsByTransaction->get($transaction->id_transaksi, collect()) as $detail)
                            <li>
                                <span>{{ $detail->nama_produk }} × {{ $detail->jumlah }}</span>
                                <strong>Rp {{ number_format($detail->subtotal_harga, 0, ',', '.') }}</strong>
                            </li>
                        @endforeach
                    </ul>
                    <footer>
                        <span>Total</span>
                        <strong>Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</strong>
                    </footer>
                </article>
            @empty
                <div class="account-card profile-history__empty">
                    <h3>Belum ada checkout</h3>
                    <p>Pesanan yang sudah di-checkout akan muncul di sini.</p>
                    <a class="button button--brown" href="{{ route('catalog.index') }}#menu">Lihat menu</a>
                </div>
            @endforelse
        </section>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="remove-button">Keluar dari akun</button>
        </form>
    @endguest
</main>
@endsection
