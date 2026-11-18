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
                <a class="button button--cream" href="#menu">Pesan Sekarang <span aria-hidden="true">→</span></a>
                <a class="button button--outline" href="#menu">Lihat Menu</a>
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
            <p class="eyebrow">Menu favorit pelanggan</p>
            <h2>Popular Brews <em>&amp; Bites</em></h2>
            <p>Diracik dengan teliti, disajikan dengan hangat. Temukan favoritmu di sini.</p>
        </div>

        <nav class="category-nav" aria-label="Kategori menu">
            @foreach (['Semua', 'Kopi', 'Non-Kopi', 'Camilan'] as $namaKategori)
                <a href="{{ route('catalog.index', ['kategori' => $namaKategori]) }}#menu"
                    @class(['category-link', 'is-active' => $kategoriAktif === $namaKategori])>
                    {{ $namaKategori }}
                </a>
            @endforeach
        </nav>

        <div class="menu-grid">
            @forelse ($menu as $item)
                <article class="menu-card">
                    <div @class(['menu-card__photo', 'menu-card__photo--feature' => $item['id_menu'] === 1])>
                        <img
                            src="{{ $item['id_menu'] === 1 ? Vite::asset('resources/img/coffee-cup.png') : $item['foto_url'] }}"
                            alt="{{ $item['nama_menu'] }}"
                            loading="lazy"
                            class="{{ $item['stok_realtime'] === 0 ? 'is-sold-out' : '' }}"
                        >
                        <span class="menu-card__tag">{{ $item['kategori'] }}</span>
                        @if ($item['stok_realtime'] === 0)
                            <span class="stock-badge stock-badge--empty">Habis</span>
                        @elseif ($item['stok_realtime'] <= 5)
                            <span class="stock-badge">Stok menipis</span>
                        @endif
                    </div>
                    <div class="menu-card__body">
                        <div>
                            <p class="menu-card__category">{{ $item['kategori'] }} <span aria-hidden="true">·</span> Favorit</p>
                            <h3>{{ $item['nama_menu'] }}</h3>
                            <p class="menu-card__price">Rp {{ number_format($item['harga'], 0, ',', '.') }}</p>
                        </div>
                        <form method="POST" action="{{ route('cart.store') }}">
                            @csrf
                            <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                            <button
                                type="submit"
                                class="add-button"
                                aria-label="Tambah {{ $item['nama_menu'] }} ke keranjang"
                                @guest onclick="alert('login dulu'); window.location.href = '{{ route('login', ['next' => 'home']) }}'; return false;" @endguest
                                @disabled($item['stok_realtime'] === 0)
                            ><span aria-hidden="true">+</span></button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="menu-empty">
                    <strong>Menu belum tersedia</strong>
                    <p>Belum ada menu di kategori ini. Coba pilih kategori lainnya.</p>
                </div>
            @endforelse
        </div>

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
                    <li><span>01</span><div><h3>100% biji pilihan petani Nusantara</h3><p>Bekerja sama langsung dengan petani dari Aceh Gayo, Toraja, hingga Bajawa Flores secara fair-trade.</p></div></li>
                    <li><span>02</span><div><h3>Pemesanan kilat &amp; tanpa antre</h3><p>Pesan langsung dari gawaimu untuk pick-up di outlet atau antar ke tempatmu.</p></div></li>
                    <li><span>03</span><div><h3>Kemasan ramah lingkungan</h3><p>Semua cup take-away dan sedotan berbahan dasar serat jagung yang terurai secara alami.</p></div></li>
                </ol>
            </div>
        </div>
    </section>

    <section class="promo-section" id="promo">
        <div class="promo-card">
            <div>
                <p class="promo-label">Khusus pelanggan baru</p>
                <h2>Dapatkan diskon 30% untuk<br class="desktop-break"> pesanan pertamamu!</h2>
                <p>Gunakan kode voucher <strong>KALA30</strong> saat checkout di aplikasi atau web.</p>
            </div>
            <a class="button button--gold" href="#menu">Klaim Voucher Sekarang <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>

<footer class="site-footer" id="footer">
    <div class="site-footer__inner">
        <div class="footer-brand">
            <a href="#home" class="brand">
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
            <p>Jl. Senopati Raya No. 42, Kebayoran Baru, Jakarta Selatan, 12190</p>
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
