<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#48291b">
    <title>Masuk · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story">
            <img src="{{ asset('images/login-barista.png') }}" alt="" class="auth-story__image">
            <a href="{{ route('catalog.index') }}" class="auth-brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>ARTISAN ROASTERY</small></span>
            </a>
            <div class="auth-story__quote">
                <span class="eyebrow eyebrow--light">Diracik dengan sepenuh hati</span>
                <h2>“Setiap cangkir punya cerita. Mari lanjutkan ceritamu.”</h2>
                <p>— Teman ngopi setiap harimu</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <nav class="auth-switch" aria-label="Akun">
                    <a class="is-active" href="{{ route('login', request()->only('next')) }}">Masuk</a>
                    <a href="{{ route('register', request()->only('next')) }}">Daftar</a>
                </nav>
                <p class="eyebrow">Selamat datang kembali</p>
                <h1>Saatnya ngopi lagi.</h1>
                <p class="auth-intro">Masuk untuk pesan lebih cepat, simpan pesanan, dan nikmati kejutan spesial dari kami.</p>
                @if (session('status'))
                    <p class="form-notice form-notice--success auth-notice" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                    @csrf
                    @if (request('next') === 'home')
                        <input type="hidden" name="next" value="home">
                    @endif
                    <label for="email">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="kamu@email.com" autocomplete="email" required autofocus>
                    @error('email')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                    @error('password')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <div class="login-options">
                        <label class="remember-option" for="remember">
                            <input id="remember" name="remember" type="checkbox" value="1">
                            <span>Ingat saya</span>
                        </label>
                        <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
                    </div>

                    <button class="auth-submit" type="submit">Masuk ke akun <span aria-hidden="true">→</span></button>
                </form>

                <p class="auth-footer-copy">Belum punya akun? <a href="{{ route('register', request()->only('next')) }}">Daftar sekarang</a></p>
                <a class="auth-back-link" href="{{ route('catalog.index') }}">← Kembali ke beranda</a>
                <p class="auth-legal">© {{ date('Y') }} KOPI KALA AIRA · DIBUAT UNTUK PECINTA KOPI</p>
            </div>
        </section>
    </main>
</body>
</html>
