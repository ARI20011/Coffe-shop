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
            <div class="profile-card__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
            <div class="profile-card__info">
                <p class="eyebrow">Profil pelanggan</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                <span>Bergabung sejak {{ $user->created_at->format('d M Y') }}</span>
            </div>
            <a class="button button--profile-outline" href="{{ route('cart.index') }}">Lihat keranjang</a>
        </section>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="remove-button">Keluar dari akun</button>
        </form>
    @endguest
</main>
@endsection
