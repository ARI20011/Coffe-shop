<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#48291b">
    <title>Atur Ulang Kata Sandi · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story">
            <img src="{{ Vite::asset('resources/img/barista.png') }}" alt="" class="auth-story__image">
            <a href="{{ route('catalog.index') }}" class="auth-brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>ARTISAN ROASTERY</small></span>
            </a>
            <div class="auth-story__quote">
                <span class="eyebrow eyebrow--light">Satu langkah lagi</span>
                <h2>Buat kata sandi baru yang aman untuk akunmu.</h2>
                <p>— Kopi Kala Aira</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <p class="eyebrow">Pemulihan akun</p>
                <h1>Kata sandi baru.</h1>
                <p class="auth-intro">Gunakan kata sandi baru minimal 8 karakter.</p>

                <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <label for="email">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" placeholder="kamu@email.com" autocomplete="email" required autofocus>
                    @error('email')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password">Kata sandi baru</label>
                    <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                    @error('password')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password_confirmation">Konfirmasi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required>

                    <button class="auth-submit" type="submit">Simpan kata sandi <span aria-hidden="true">→</span></button>
                </form>

                <p class="auth-footer-copy"><a href="{{ route('login') }}">Kembali ke login</a></p>
                <p class="auth-legal">© {{ date('Y') }} KOPI KALA AIRA · DIBUAT UNTUK PECINTA KOPI</p>
            </div>
        </section>
    </main>
</body>
</html>
