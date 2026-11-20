@extends('layouts.app')

@section('title', 'Top up saldo')

@section('content')
<main class="account-page topup-page">
    <div class="account-heading">
        <p class="eyebrow">Saldo internal</p>
        <h1>Top up <em>saldo.</em></h1>
        <p>Tambahkan saldo Coffee Shop menggunakan metode pembayaran pilihan.</p>
    </div>

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

    <section class="account-card topup-card">
        <div class="topup-balance">
            <span>Saldo saat ini</span>
            <strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong>
        </div>
        <p class="topup-demo-notice">Mode uji coba: top up berhasil otomatis dan belum memproses pembayaran melalui gateway Dana, QRIS, atau GoPay.</p>
        <form method="POST" action="{{ route('topup.store') }}" class="topup-form">
            @csrf
            <label for="topup-amount">Nominal top up</label>
            <input id="topup-amount" type="number" name="jumlah" min="10000" max="5000000" step="1000"
                value="{{ old('jumlah', 50000) }}" required>
            @error('jumlah') <span class="field-error">{{ $message }}</span> @enderror

            <label for="topup-method">Metode pembayaran</label>
            <select id="topup-method" name="metode_pembayaran" required>
                @foreach ($methods as $value => $label)
                    <option value="{{ $value }}" @selected(old('metode_pembayaran', 'qris') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('metode_pembayaran') <span class="field-error">{{ $message }}</span> @enderror

            <button class="button button--brown" type="submit">Konfirmasi top up simulasi</button>
        </form>
    </section>

    <section class="profile-history topup-history" aria-labelledby="topup-history-title">
        <div class="profile-history__heading">
            <p class="eyebrow">Riwayat saldo</p>
            <h2 id="topup-history-title">Top up terakhir</h2>
        </div>
        @forelse ($topups as $topup)
            <article class="profile-order">
                <header class="profile-order__header">
                    <div>
                        <h3>Rp {{ number_format($topup->jumlah, 0, ',', '.') }} · {{ strtoupper($topup->metode_pembayaran) }}</h3>
                        <time datetime="{{ \Illuminate\Support\Carbon::parse($topup->created_at)->toAtomString() }}">
                            {{ \Illuminate\Support\Carbon::parse($topup->created_at)->format('d M Y, H:i') }}
                        </time>
                    </div>
                    <span class="profile-order__status">{{ ucfirst($topup->status_topup) }}</span>
                </header>
            </article>
        @empty
            <div class="account-card profile-history__empty">
                <p>Belum ada riwayat top up.</p>
            </div>
        @endforelse
    </section>
</main>
@endsection
