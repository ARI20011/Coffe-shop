@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<main class="account-page checkout-page">
    <div class="account-heading">
        <p class="eyebrow">Konfirmasi pesanan</p>
        <h1>Checkout <em>{{ $orderType === 'dine_in' ? 'dine-in.' : 'take away.' }}</em></h1>
        <p>Periksa jadwal, lalu bayar menggunakan saldo Coffee Shop.</p>
    </div>

    @if ($errors->any())
        <div class="form-notice form-notice--error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="checkout-layout">
        <form class="account-card checkout-form" method="POST" action="{{ route('checkout.store') }}">
            @csrf
            <input type="hidden" name="order_type" value="{{ $orderType }}">
            @if ($orderType === 'dine_in')
                <input type="hidden" name="table_id" value="{{ $table->id_meja }}">
            @endif

            <section class="checkout-section">
                <h2>Jadwal pesanan</h2>
                @if ($orderType === 'dine_in')
                    <p>Meja: <strong>{{ $table->nomor_meja }}</strong></p>
                    <label for="arrival-at">Waktu tiba di lokasi</label>
                    <input id="arrival-at" type="datetime-local" name="arrival_at"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        value="{{ old('arrival_at', $scheduledAt) }}" required>
                    @error('arrival_at') <span class="field-error">{{ $message }}</span> @enderror
                @else
                    <label for="pickup-at">Waktu pengambilan pesanan</label>
                    <input id="pickup-at" type="datetime-local" name="pickup_at"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        value="{{ old('pickup_at', $scheduledAt) }}" required>
                    @error('pickup_at') <span class="field-error">{{ $message }}</span> @enderror
                @endif
            </section>

            <section class="checkout-section">
                <h2>Metode pembayaran</h2>
                <p class="checkout-balance">
                    Saldo Coffee Shop saat ini:
                    <strong>Rp {{ number_format($saldo, 0, ',', '.') }}</strong>
                    <a href="{{ route('topup.index') }}">Top up</a>
                </p>
                <div class="checkout-methods">
                    @foreach ($paymentMethods as $value => $label)
                        <label class="checkout-method">
                            <input type="radio" name="metode_pembayaran" value="{{ $value }}"
                                @checked(old('metode_pembayaran', 'saldo_coffee_shop') === $value)>
                            <span>
                                <strong>{{ $label }}</strong>
                                <small>Pembayaran langsung memotong saldo dan status pembayaran menjadi berhasil.</small>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="checkout-disabled-title">Metode berikut hanya tersedia untuk top up saldo:</p>
                <div class="checkout-methods checkout-methods--disabled" aria-label="Metode yang tidak tersedia untuk checkout">
                    @foreach ($topUpOnlyMethods as $label)
                        <div class="checkout-method checkout-method--disabled" aria-disabled="true">
                            <input type="radio" disabled>
                            <span><strong>{{ $label }}</strong><small>Gunakan hanya di halaman top up saldo Coffee Shop.</small></span>
                        </div>
                    @endforeach
                </div>
                <a class="checkout-topup-link" href="{{ route('topup.index') }}">Top up saldo Coffee Shop →</a>
                @error('metode_pembayaran') <span class="field-error">{{ $message }}</span> @enderror
            </section>

            <section class="checkout-section" aria-labelledby="checkout-voucher-title">
                <h2 id="checkout-voucher-title">Pakai voucher</h2>
                <p>Pilih satu voucher yang memenuhi syarat pesanan ini. Voucher akan hangus setelah checkout berhasil.</p>
                <div class="checkout-voucher-options">
                    <label class="checkout-voucher-option">
                        <input type="radio" name="voucher_id" value=""
                            @checked(old('voucher_id', $selectedVoucherId) === null || old('voucher_id', $selectedVoucherId) === '')>
                        <span><strong>Tanpa voucher</strong><small>Bayar total normal.</small></span>
                        <b data-voucher-discount="0">Rp 0</b>
                    </label>
                    @foreach ($eligibleVouchers as $voucher)
                        <label class="checkout-voucher-option">
                            <input type="radio" name="voucher_id" value="{{ $voucher->id_voucher_pelanggan }}"
                                data-voucher-discount="{{ $voucher->potongan }}"
                                @checked((string) old('voucher_id', $selectedVoucherId) === (string) $voucher->id_voucher_pelanggan)>
                            <span>
                                <strong>{{ $voucher->nama_voucher }}</strong>
                                <small>{{ $voucher->deskripsi }}</small>
                            </span>
                            <b>- Rp {{ number_format($voucher->potongan, 0, ',', '.') }}</b>
                        </label>
                    @endforeach
                </div>
                @if ($eligibleVouchers->isEmpty())
                    <p class="checkout-voucher-empty">Belum ada voucher yang memenuhi syarat. Lihat misi dan promo di <a href="{{ route('vouchers.index') }}">halaman voucher</a>.</p>
                @endif
                @error('voucher_id') <span class="field-error">{{ $message }}</span> @enderror
            </section>

            <button class="button button--brown checkout-submit" type="submit">Bayar dan buat pesanan</button>
            <a class="button button--profile-outline checkout-back" href="{{ route('cart.index') }}">Kembali ke keranjang</a>
        </form>

        <aside class="account-card checkout-summary" aria-labelledby="checkout-summary-title">
            <h2 id="checkout-summary-title">Ringkasan pesanan</h2>
            @foreach ($items as $item)
                <div class="checkout-summary__item">
                    <span>{{ $item['nama_menu'] }} × {{ $item['jumlah'] }}</span>
                    <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                </div>
            @endforeach
            <div class="checkout-summary__item">
                <span>Subtotal</span>
                <strong id="checkout-subtotal" data-subtotal="{{ $subtotal }}">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
            </div>
            <div class="checkout-summary__item checkout-summary__discount">
                <span>Diskon voucher</span>
                <strong id="checkout-discount">- Rp {{ number_format($discount, 0, ',', '.') }}</strong>
            </div>
            <div class="checkout-summary__total">
                <span>Total</span>
                <strong id="checkout-total" data-total="{{ $total }}">Rp {{ number_format($total, 0, ',', '.') }}</strong>
            </div>
        </aside>
    </div>
</main>

<script>
    const checkoutVoucherInputs = document.querySelectorAll('input[name="voucher_id"]');
    const checkoutSubtotal = Number(document.getElementById('checkout-subtotal').dataset.subtotal);
    const checkoutDiscount = document.getElementById('checkout-discount');
    const checkoutTotal = document.getElementById('checkout-total');
    const rupiah = new Intl.NumberFormat('id-ID');

    checkoutVoucherInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const discount = Number(input.dataset.voucherDiscount || 0);
            checkoutDiscount.textContent = `- Rp ${rupiah.format(discount)}`;
            checkoutTotal.textContent = `Rp ${rupiah.format(Math.max(0, checkoutSubtotal - discount))}`;
        });
    });
</script>
@endsection
