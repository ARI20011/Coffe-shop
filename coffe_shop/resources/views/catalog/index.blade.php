@extends('layouts.app')

@section('title', 'Kopi untuk Harimu')

@section('content')
<main>
    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif
    @error('cart')
        <p class="form-notice form-notice--error" role="alert">{{ $message }}</p>
    @enderror

    <section class="hero" id="home">
        <div class="hero__copy">
            <p class="eyebrow eyebrow--light"><span></span> Fresh batch roasted today</p>
            <h1>Kopi yang diracik<br>untuk <em>harimu.</em></h1>
            <p class="hero__description">
                Pesan cepat, nikmati racikan segar dan kopi pilihan yang menemani setiap cerita.
                Dibuat sepenuh hati, dari biji pertama sampai tegukan terakhir.
            </p>
            <div class="hero__actions">
                <a class="button button--cream" href="{{ route('dashboard.index', ['page' => 'menu']) }}">Pesan Sekarang <span aria-hidden="true">→</span></a>
                <a class="button button--outline" href="{{ route('dashboard.index', ['page' => 'menu']) }}">Lihat Menu</a>
            </div>
            <div class="hero__stats" aria-label="Keunggulan Kopi Kala Aira">
                <div><strong>100%</strong><span>Biji pilihan Nusantara</span></div>
                <div><strong>15 min</strong><span>Siap untuk dinikmati</span></div>
                <div><strong>4.9 <span aria-hidden="true">★</span></strong><span>12rb+ review positif</span></div>
            </div>
        </div>
        <div class="hero__visual">
            <img src="{{ Vite::asset('resources/img/iced-coffee.png') }}" alt="Kopi dingin berlapis susu dan espresso">
            <div class="hero__caption">
                <span class="hero__caption-icon" aria-hidden="true">✦</span>
                <span><strong>Diracik segar setiap hari</strong><small>Temukan teman baru untuk harimu.</small></span>
            </div>
            <span class="hero__stamp" aria-hidden="true">GOOD<br>COFFEE<br>GOOD DAY</span>
        </div>
    </section>

    <section class="menu-section section-wrap" id="menu">
        <div class="section-heading">
            <p class="eyebrow">{{ $bestSellerIds === [] ? 'Menu pilihan' : 'Menu terlaris pelanggan' }}</p>
            <h2>Popular Brews <em>&amp; Bites</em></h2>
            <p>Menu yang paling banyak dipesan, diracik segar untuk menemani harimu.</p>
        </div>

        <nav class="category-nav" aria-label="Kategori menu">
            @foreach (array_merge(['Semua'], $categories) as $namaKategori)
                <a href="{{ route('dashboard.index', ['page' => 'home', 'kategori' => $namaKategori]) }}#menu"
                    @class(['category-link', 'is-active' => $kategoriAktif === $namaKategori])>
                    {{ $namaKategori }}
                </a>
            @endforeach
        </nav>

        @include('catalog.partials.menu-grid')

    </section>

    <section class="about-section" id="about">
        <div class="about-section__inner">
            <div class="about-photo">
                <img src="{{ Vite::asset('resources/img/barista.png') }}" alt="Barista meracik kopi dengan teliti" loading="lazy">
                <blockquote>
                    “Setiap cangkir adalah cerita akan konsistensi dan kebahagiaan rasa.”
                    <cite>— Head Barista, Kopi Kala Aira</cite>
                </blockquote>
            </div>
            <div class="about-copy">
                <p class="eyebrow">Kenapa memilih kami</p>
                <h2>Menghadirkan sensasi ngopi terbaik, <em>langsung ke meja Anda.</em></h2>
                <p class="about-copy__lead">Kami percaya kopi yang luar biasa bukan hanya soal kafein, melainkan jeda berharga di tengah hari yang sibuk dan cerita yang tercipta di setiap tegukan.</p>
                <ol class="benefit-list">
                    <li><span>01</span><div><h3>100% biji pilihan petani Nusantara</h3><p>Bekerja sama dengan petani kopi dari Ende, Flores, dan berbagai daerah Nusantara.</p></div></li>
                    <li><span>02</span><div><h3>Pemesanan kilat &amp; tanpa antre</h3><p>Pesan langsung dari gawaimu untuk pick-up di outlet atau antar ke tempatmu.</p></div></li>
                    <li><span>03</span><div><h3>Kemasan ramah lingkungan</h3><p>Semua cup take-away dan sedotan berbahan dasar serat jagung yang terurai secara alami.</p></div></li>
                </ol>
            </div>
        </div>
    </section>

    @if ($showPromo)
        <section class="promo-section" id="promo">
            <div class="promo-card">
                <div>
                    <p class="promo-label">Voucher sambutan · tersisa {{ $promoClaimsLeft }} dari 15</p>
                    <h2>Diskon 30% untuk pesanan dine-in</h2>
                    <p>Masukkan kode <strong>KALA30</strong> di halaman voucher. Berlaku untuk dine-in minimal 3 produk, selama kuota tersedia.</p>
                </div>
                <a class="button button--gold" href="{{ route('vouchers.index') }}">Klaim Voucher <span aria-hidden="true">→</span></a>
            </div>
        </section>
    @endif
</main>

<footer class="site-footer" id="footer">
    <div class="site-footer__inner">
        <div class="footer-brand">
            <a href="{{ route('dashboard.index', ['page' => 'home']) }}" class="brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira</span>
            </a>
            <p>Menemani hari aktif Anda dengan racikan kopi artisan modern, suasana hangat, dan kemudahan pemesanan digital.</p>
            <div class="social-links" aria-label="Media sosial"><a href="#footer" aria-label="Instagram">ig</a><a href="#footer" aria-label="TikTok">tk</a></div>
        </div>
        <div class="footer-column">
            <h2>Jam operasional</h2>
            <p><span>Senin – Jumat</span><strong>07:00 – 22:00</strong></p>
            <p><span>Sabtu – Minggu</span><strong>07:00 – 23:00</strong></p>
            <p><span>Layanan delivery</span><strong class="footer-highlight">Tersedia setiap hari</strong></p>
        </div>
        <div class="footer-column">
            <h2>Lokasi kedai</h2>
            <p>Ende, Nusa Tenggara Timur, Indonesia</p>
            <p>WhatsApp Hotline: <a href="tel:+6281234567890">+62 812-3456-7890</a></p>
        </div>
        <div class="footer-column">
            <h2>Bantuan &amp; info</h2>
            <a href="#menu">Cara pesan online</a>
            <a href="#promo">Kemitraan &amp; event catering</a>
            <a href="#footer">Syarat &amp; ketentuan layanan</a>
            <a href="#footer">Kebijakan privasi</a>
        </div>
    </div>
    <div class="site-footer__bottom">
        <span>© {{ date('Y') }} Kopi Kala Aira Indonesia. Seluruh hak cipta dilindungi.</span>
        <span>Handcrafted with passion for coffee lovers.</span>
    </div>
</footer>
@endsection
