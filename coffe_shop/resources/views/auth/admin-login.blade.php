<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#051F20">
    <title>Login Admin/Kasir · Kopi Kala Aira</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page auth-page--staff">
    <main class="auth-shell auth-shell--staff auth-shell--entering">
        <section class="auth-story">
            <img src="{{ asset('images/login-barista.png') }}" alt="" class="auth-story__image">
            <a href="{{ route('catalog.index') }}" class="auth-brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>STAFF PORTAL</small></span>
            </a>
            <div class="auth-story__quote">
                <span class="eyebrow eyebrow--light">Portal staf</span>
                <h2>Kelola kedai dengan rapi dan penuh perhatian.</h2>
                <p>— Admin & kasir Kopi Kala Aira</p>
            </div>
        </section>

        <section class="auth-form-side">
            <div class="auth-form-wrap">
                <p class="eyebrow">Admin & kasir</p>
                <h1>Masuk ke portal staf.</h1>
                <p class="auth-intro">Gunakan username dan kata sandi akun staf yang telah diberikan.</p>

                @if (session('status'))
                    <p class="form-notice form-notice--success auth-notice" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form">
                    @csrf
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Username staf" autocomplete="username" maxlength="100" required autofocus>
                    @error('username')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                    @error('password')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror

                    <button class="auth-submit" type="submit">Masuk sebagai staf <span aria-hidden="true">→</span></button>
                </form>

                <a class="auth-back-link" href="{{ route('register') }}" data-auth-flip>Kembali ke daftar pelanggan</a>
                <p class="auth-legal">© {{ date('Y') }} KOPI KALA AIRA · PORTAL STAF</p>
            </div>
        </section>
    </main>
</body>
</html>
