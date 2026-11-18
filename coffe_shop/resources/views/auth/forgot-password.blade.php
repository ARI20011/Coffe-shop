<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#48291b">
    <title>Lupa Kata Sandi · Kopi Kala Aira</title>
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
                <span class="eyebrow eyebrow--light">Kami siap membantu</span>
                <h2>Kembali ke akunmu dan lanjutkan cerita.</h2>
                <p>— Teman ngopi setiap harimu</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <p class="eyebrow">Pemulihan akun</p>
                <h1>Lupa kata sandi?</h1>
                <p class="auth-intro">Masukkan email akunmu. Jika terdaftar, kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>

                @if (session('status'))
                    <p class="form-notice form-notice--success auth-notice" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                    @csrf
                    <label for="email">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="kamu@email.com" autocomplete="email" required autofocus>
                    @error('email')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                    <button class="auth-submit" type="submit">Kirim tautan reset <span aria-hidden="true">→</span></button>
                </form>

                <p class="auth-footer-copy">Ingat kata sandi? <a href="{{ route('login') }}">Kembali ke login</a></p>
                <a class="auth-back-link" href="{{ route('catalog.index') }}">← Kembali ke beranda</a>
                <p class="auth-legal">© {{ date('Y') }} KOPI KALA AIRA · DIBUAT UNTUK PECINTA KOPI</p>
            </div>
        </section>
    </main>
</body>
</html>
