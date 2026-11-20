<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#48291b">
    <title>Daftar · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story">
            <img src="{{ asset('images/login-barista.png') }}" alt="" class="auth-story__image">
            <a class="auth-admin-switch" href="{{ route('admin.login') }}" data-auth-flip aria-label="Masuk sebagai admin atau kasir">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <a href="{{ route('catalog.index') }}" class="auth-brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>ARTISAN ROASTERY</small></span>
            </a>
            <div class="auth-story__quote">
                <span class="eyebrow eyebrow--light">Bergabung bersama kami</span>
                <h2>“Setiap cangkir punya cerita. Mari mulai ceritamu.”</h2>
                <p>— Teman ngopi setiap harimu</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <nav class="auth-switch" aria-label="Akun">
                    <a href="{{ route('login', request()->only('next')) }}">Masuk</a>
                    <a class="is-active" href="{{ route('register', request()->only('next')) }}">Daftar</a>
                </nav>
                <p class="eyebrow">Bergabung bersama kami</p>
                <h1>Mulai cerita barumu.</h1>
                <p class="auth-intro">Buat akun untuk menyimpan keranjang dan menikmati pengalaman ngopi yang lebih praktis.</p>

                <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                    @csrf
                    <input type="hidden" name="referral_code" value="{{ old('referral_code', request()->query('ref')) }}">
                    <label for="name">Nama lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama kamu" autocomplete="name" required autofocus>
                    @error('name')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Username pilihanmu" autocomplete="username" maxlength="100" required>
                    @error('username')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="email">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="kamu@email.com" autocomplete="email" required>
                    @error('email')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                    @error('password')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password_confirmation">Konfirmasi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required>

                    <label class="captcha-widget" for="captcha">
                        <input id="captcha" name="captcha" type="checkbox" value="1" required>
                        <span>I'm not a robot</span>
                        <span class="captcha-widget__mark" aria-hidden="true"></span>
                    </label>
                    @error('captcha')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label class="remember-option terms-option" for="terms">
                        <input id="terms" name="terms" type="checkbox" value="1" required>
                        <span>Saya menyetujui syarat dan ketentuan serta kebijakan privasi.</span>
                    </label>
                    @error('terms')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <button class="auth-submit" type="submit">Buat akun saya <span aria-hidden="true">→</span></button>
                </form>

                <p class="auth-footer-copy">Sudah punya akun? <a href="{{ route('login', request()->only('next')) }}">Masuk di sini</a></p>
                <a class="auth-back-link" href="{{ route('catalog.index') }}">← Kembali ke beranda</a>
                <p class="auth-legal">© {{ date('Y') }} KOPI KALA AIRA · DIBUAT UNTUK PECINTA KOPI</p>
            </div>
        </section>
    </main>
</body>
</html>
