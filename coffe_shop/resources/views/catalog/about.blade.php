@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
<main class="about-page">
    <section class="about-hero" style="--about-hero-image: url('{{ Vite::asset('resources/img/iced-coffee.png') }}')">
        <div class="about-hero__content">
            <p class="about-page-eyebrow">Didedikasikan untuk kopi Flores</p>
            <h1>Kisah di balik setiap cangkir <em>Kopi Kala Aira</em></h1>
            <p>Berawal dari kehangatan Ende, kami meracik kopi Nusantara dengan sepenuh hati, menghargai asal biji, dan menyambut setiap pelanggan seperti keluarga.</p>
            <div class="about-hero__actions">
                <a class="button button--cream" href="{{ route('dashboard.index', ['page' => 'menu']) }}">Jelajahi menu <span aria-hidden="true">→</span></a>
                <a class="button about-button--glass" href="#lokasi">Temukan kedai kami</a>
            </div>
            <div class="about-hero__facts">
                <div><strong>Flores</strong><span>Rumah rasa kami</span></div>
                <div><strong>Ende</strong><span>Nusa Tenggara Timur</span></div>
                <div><strong>Sepenuh hati</strong><span>Diracik setiap hari</span></div>
            </div>
        </div>
    </section>

    <section class="about-philosophy">
        <header class="about-section-heading">
            <p class="about-page-eyebrow">Integritas rasa dan nilai</p>
            <h2>Filosofi <em>seduhan</em> kami</h2>
            <p>Setiap pilihan dari biji hingga cangkir mencerminkan kepedulian pada rasa, manusia, dan lingkungan.</p>
        </header>
        <div class="about-value-grid">
            <article class="about-value-card">
                <span aria-hidden="true">✦</span>
                <p>Prinsip 01</p>
                <h3>Biji pilihan Nusantara</h3>
                <p>Kami memilih kopi dari petani dan daerah penghasil lokal, termasuk Flores, agar karakter tiap biji tetap terasa di setiap seduhan.</p>
            </article>
            <article class="about-value-card">
                <span aria-hidden="true">◉</span>
                <p>Prinsip 02</p>
                <h3>Diracik dengan teliti</h3>
                <p>Takaran, suhu, dan waktu seduh diperhatikan oleh tim kami untuk menjaga rasa yang konsisten, tanpa menghilangkan ciri khas bijinya.</p>
            </article>
            <article class="about-value-card">
                <span aria-hidden="true">♻</span>
                <p>Prinsip 03</p>
                <h3>Lebih bijak setiap hari</h3>
                <p>Kami terus berupaya mengurangi sampah kemasan dan menggunakan bahan sesuai kebutuhan, satu kebiasaan kecil pada satu waktu.</p>
            </article>
        </div>
    </section>

    <section class="about-journey">
        <header class="about-section-heading">
            <p class="about-page-eyebrow">Dari kebun hingga ke cangkir</p>
            <h2>Perjalanan secangkir <em>kopi</em></h2>
            <p>Rasa yang baik lahir dari perhatian di setiap tahapnya.</p>
        </header>
        <div class="about-journey-grid">
            <article><span>01</span><h3>Memilih biji</h3><p>Mengenal asal dan karakter kopi pilihan dari Flores serta penjuru Nusantara.</p></article>
            <article><span>02</span><h3>Menjaga kesegaran</h3><p>Biji disimpan dan disiapkan dengan cermat agar aroma dan kualitasnya terjaga.</p></article>
            <article><span>03</span><h3>Menyeduh presisi</h3><p>Setiap racikan disesuaikan dengan karakter biji dan pesanan yang dipilih.</p></article>
            <article><span>04</span><h3>Disajikan hangat</h3><p>Tim kami menyelesaikan setiap pesanan dengan perhatian pada detail.</p></article>
        </div>
    </section>

    <section class="about-team">
        <header class="about-section-heading">
            <p class="about-page-eyebrow">Sosok di balik kehangatan</p>
            <h2>Bertemu karyawan <em>terbaik kami</em></h2>
            <p>Orang-orang yang membawa keramahan, ketelitian, dan semangat ke dalam setiap cangkir.</p>
        </header>
        <div class="about-team-grid">
            @forelse ($employees as $employee)
                <article class="about-team-card">
                    <div class="about-team-card__portrait" aria-hidden="true">{{ mb_strtoupper(mb_substr($employee->name, 0, 1)) }}</div>
                    <div class="about-team-card__copy">
                        <span>{{ ucfirst($employee->role) }} · Tim Kopi Kala Aira</span>
                        <h3>{{ $employee->name }}</h3>
                        <p>Menyambut pelanggan dan ikut menjaga pengalaman menikmati kopi yang hangat dan berkesan.</p>
                    </div>
                </article>
            @empty
                <article class="about-team-card about-team-card--welcome">
                    <div class="about-team-card__portrait" aria-hidden="true">K</div>
                    <div class="about-team-card__copy">
                        <span>Tim Kopi Kala Aira</span>
                        <h3>Diracik bersama, disajikan dengan hangat.</h3>
                        <p>Karyawan terbaik kami bekerja bersama untuk memberikan pelayanan dan racikan yang berkesan.</p>
                    </div>
                </article>
            @endforelse
        </div>
    </section>

    <section class="about-location" id="lokasi">
        <header class="about-section-heading">
            <p class="about-page-eyebrow">Ruang temu dan kehangatan</p>
            <h2>Kunjungi kedai kami <em>di Ende</em></h2>
            <p>Temukan tempat untuk menikmati kopi, berbagi cerita, dan beristirahat sejenak di Flores.</p>
        </header>
        <article class="about-location-card">
            <iframe
                title="Peta lokasi Ende, Nusa Tenggara Timur"
                src="https://www.openstreetmap.org/export/embed.html?bbox=121.60%2C-8.88%2C121.72%2C-8.80&amp;layer=mapnik&amp;marker=-8.8432%2C121.6627"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
            <div>
                <p class="about-page-eyebrow">Flores, Nusa Tenggara Timur</p>
                <h3>Kopi Kala Aira · Ende</h3>
                <p>Ende, Kabupaten Ende, Nusa Tenggara Timur, Indonesia</p>
                <p>Persinggahan hangat untuk kopi dan cerita di jantung Flores.</p>
                <a href="https://www.openstreetmap.org/?mlat=-8.8432&amp;mlon=121.6627#map=14/-8.8432/121.6627" target="_blank" rel="noopener noreferrer">Lihat peta Ende <span aria-hidden="true">↗</span></a>
            </div>
        </article>
    </section>

    <section class="about-cta">
        <div>
            <p class="about-page-eyebrow">Hangatnya kopi, dekat denganmu</p>
            <h2>Rasakan racikan terbaik <em>hari ini</em></h2>
            <p>Pilih menu favoritmu dan nikmati racikan Kopi Kala Aira.</p>
        </div>
        <a class="button button--cream" href="{{ route('dashboard.index', ['page' => 'menu']) }}">Pesan racikan <span aria-hidden="true">→</span></a>
    </section>
</main>

<footer class="site-footer about-page-footer">
    <div class="site-footer__inner">
        <div class="footer-brand">
            <a href="{{ route('dashboard.index', ['page' => 'home']) }}" class="brand">
                <span class="brand__mark" aria-hidden="true">K</span>
                <span class="brand__name">Kopi Kala Aira<small>FLORES, NTT</small></span>
            </a>
            <p>Menyajikan racikan kopi Nusantara dengan keramahan khas Ende.</p>
        </div>
        <div class="footer-column">
            <h2>Jam operasional</h2>
            <p>Senin – Minggu</p>
            <p><strong>Setiap hari · Ende, NTT</strong></p>
        </div>
        <div class="footer-column">
            <h2>Lokasi kedai</h2>
            <p>Ende, Kabupaten Ende, Nusa Tenggara Timur, Indonesia</p>
        </div>
        <div class="footer-column">
            <h2>Jelajahi</h2>
            <a href="{{ route('dashboard.index', ['page' => 'menu']) }}">Menu</a>
            <a href="{{ route('about.index') }}">Tentang kami</a>
        </div>
    </div>
    <div class="site-footer__bottom">
        <span>© {{ date('Y') }} Kopi Kala Aira · Ende, Nusa Tenggara Timur</span>
        <span>Dibuat dengan sepenuh hati.</span>
    </div>
</footer>
@endsection
