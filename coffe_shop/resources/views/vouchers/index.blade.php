@extends('layouts.app')

@section('title', 'Promo & Voucher')

@section('content')
<main class="voucher-page">
    <header class="voucher-heading">
        <div>
            <p class="eyebrow">Member Kopi Kala Aira · Promo berjalan</p>
            <h1>Promo spesial &amp; <em>voucher reward</em></h1>
            <p>Selesaikan misi, tukarkan kode, atau beli voucher untuk pesanan berikutnya.</p>
        </div>
        <aside class="voucher-wallet">
            <span>Saldo Coffee Shop</span>
            <strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong>
            <a href="{{ route('topup.index') }}">Top up saldo →</a>
        </aside>
    </header>

    @if (session('status'))
        <p class="form-notice form-notice--success" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="form-notice form-notice--error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <nav class="voucher-tabs" aria-label="Halaman voucher">
        <a class="is-active" href="{{ route('vouchers.index') }}">Misi &amp; Kupon</a>
        <a href="{{ route('vouchers.mine') }}">Voucher Saya</a>
    </nav>

    <section class="voucher-intro">
        <div>
            <p class="eyebrow eyebrow--light">Perjalanan pelanggan baru</p>
            <h2>Selesaikan misi, klaim hadiahmu</h2>
            <p>Voucher yang diklaim masuk ke Voucher Saya dan dapat dipilih saat checkout jika syaratnya terpenuhi.</p>
        </div>
        <a class="button button--cream" href="{{ route('vouchers.mine') }}">Lihat voucher saya</a>
    </section>

    <section class="voucher-task-grid" aria-label="Misi voucher">
        @foreach ($tasks as $task)
            @php($percentage = (int) round(($task['progress'] / $task['goal']) * 100))
            <article class="voucher-task-card">
                <div class="voucher-task-card__top">
                    <span class="voucher-task-card__icon" aria-hidden="true">{{ ['topup' => '＋', 'orders' => '☕', 'referrals' => '♧'][$task['id']] }}</span>
                    <span class="voucher-task-card__badge">Misi {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <h3>{{ $task['title'] }}</h3>
                <p>{{ $task['description'] }}</p>
                <div class="voucher-progress" aria-label="Progres {{ $task['progress'] }} dari {{ $task['goal'] }}">
                    <span style="width: {{ $percentage }}%"></span>
                </div>
                <div class="voucher-task-card__progress">
                    <span>Progres</span>
                    <strong>{{ $task['progress'] }} / {{ $task['goal'] }}</strong>
                </div>
                <p class="voucher-task-card__reward">
                    Hadiah:
                    <strong>{{ $task['reward']->nama_voucher ?? 'Voucher reward' }}</strong>
                </p>

                @if ($task['claimed'])
                    <span class="voucher-action voucher-action--done">Sudah diklaim</span>
                @elseif ($task['progress'] >= $task['goal'])
                    <form method="POST" action="{{ route('vouchers.tasks.claim', $task['id']) }}">
                        @csrf
                        <button class="voucher-action" type="submit">Klaim hadiah</button>
                    </form>
                @elseif ($task['id'] === 'topup')
                    <a class="voucher-action" href="{{ route('topup.index') }}">Top up sekarang · Rp50.000</a>
                @elseif ($task['id'] === 'orders')
                    <a class="voucher-action" href="{{ route('dashboard.index', ['page' => 'menu']) }}">Pesan produk sekarang →</a>
                @else
                    <button class="voucher-action" type="button" data-copy-referral="{{ $referralUrl }}">Salin link &amp; bagikan</button>
                    <span class="voucher-copy-status" aria-live="polite"></span>
                @endif
            </article>
        @endforeach
    </section>

    <section class="voucher-redeem" aria-labelledby="voucher-redeem-title">
        <div>
            <p class="eyebrow">Klaim kode rahasia</p>
            <h2 id="voucher-redeem-title">Punya kode promo atau hadiah?</h2>
            <p>Masukkan kode yang kamu terima. Setiap kode hanya dapat ditukar satu kali per akun.</p>
        </div>
        <form method="POST" action="{{ route('vouchers.redeem') }}">
            @csrf
            <label class="sr-only" for="voucher-code">Kode voucher</label>
            <input id="voucher-code" name="code" value="{{ old('code') }}" placeholder="Contoh: KALA30" maxlength="40" required>
            <button type="submit">Tukarkan kode</button>
            @if ($promoClaimsLeft > 0 && ! $promoClaimed)
                <small>KALA30 · {{ $promoClaimsLeft }} klaim tersisa · dine-in minimal 3 produk</small>
            @endif
        </form>
    </section>

    <section class="voucher-catalog" aria-labelledby="voucher-catalog-title">
        <header class="voucher-section-heading">
            <div>
                <p class="eyebrow">Katalog penawaran terbatas</p>
                <h2 id="voucher-catalog-title">Beli &amp; koleksi voucher hemat</h2>
            </div>
            <a href="{{ route('vouchers.mine') }}">Voucher Saya →</a>
        </header>
        <div class="voucher-catalog-grid">
            @forelse ($catalog as $voucher)
                <article class="voucher-product-card">
                    <div class="voucher-product-card__art" aria-hidden="true">
                        <span>{{ $voucher->tipe_diskon === 'persen' ? $voucher->nilai_diskon.'%' : 'Rp' }}</span>
                        <small>VOUCHER</small>
                    </div>
                    <div class="voucher-product-card__body">
                        <p class="eyebrow">{{ strtoupper(str_replace('_', ' ', $voucher->tipe_diskon)) }}</p>
                        <h3>{{ $voucher->nama_voucher }}</h3>
                        <p>{{ $voucher->deskripsi }}</p>
                        <div class="voucher-product-card__buy">
                            <strong>Rp {{ number_format($voucher->harga_beli, 0, ',', '.') }}</strong>
                            <form method="POST" action="{{ route('vouchers.purchase', $voucher->id_voucher) }}">
                                @csrf
                                <button type="submit">Beli voucher</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <p class="voucher-empty">Semua voucher katalog sudah ada di Voucher Saya.</p>
            @endforelse
        </div>
    </section>
</main>

<script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-referral]');
        if (!button) return;

        const status = button.nextElementSibling;
        try {
            await navigator.clipboard.writeText(button.dataset.copyReferral);
            status.textContent = 'Link referral berhasil disalin.';
        } catch {
            status.textContent = button.dataset.copyReferral;
        }
    });
</script>
@endsection
