@extends('layouts.staff')

@section('title', 'Kelola transaksi')

@section('content')
<section class="staff-page-heading">
    <div>
        <p class="staff-eyebrow">Pesanan pelanggan</p>
        <h1>Kelola transaksi</h1>
        <p>Periksa pembayaran, jadwal pesanan, dan penggunaan meja.</p>
    </div>
</section>

<section class="staff-panel" aria-labelledby="dine-in-orders-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Makan di tempat</p>
            <h2 id="dine-in-orders-title">Transaksi dine-in</h2>
        </div>
        <span class="staff-page-count">{{ $dineInTransactions->total() }} pesanan</span>
    </div>
    <div class="staff-table-wrap">
        <table class="staff-table staff-transaction-table">
            <thead>
                <tr>
                    <th>ID pesanan</th><th>Nama pemesan</th><th>Hari / tanggal</th><th>Meja</th>
                    <th>Status meja</th><th>Produk dipesan</th><th>Pembayaran</th><th>Status pesanan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dineInTransactions as $transaction)
                    @php
                        $nextStatuses = array_merge(
                            [$transaction->status_pesanan],
                            $statusTransitions[$transaction->status_pesanan] ?? [],
                        );
                    @endphp
                    <tr>
                        <td><strong>#{{ $transaction->id_transaksi }}</strong></td>
                        <td>{{ $transaction->customer_name }}<small>{{ $transaction->username }}</small></td>
                        <td>
                            {{ \Illuminate\Support\Carbon::parse($transaction->tanggal_transaksi)->translatedFormat('l, d M Y') }}
                            <small>
                                Tiba:
                                {{ $transaction->waktu_tiba ? \Illuminate\Support\Carbon::parse($transaction->waktu_tiba)->format('d M Y, H:i') : '—' }}
                            </small>
                        </td>
                        <td>{{ $transaction->nomor_meja ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('cashier.tables.status', $transaction->id_meja) }}" class="staff-status-form">
                                @csrf
                                @method('PUT')
                                <select name="status_meja" aria-label="Status {{ $transaction->nomor_meja }}">
                                    @foreach ($tableStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($transaction->status_meja === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="staff-button staff-button--outline" type="submit">Ubah</button>
                            </form>
                        </td>
                        <td>
                            @foreach ($detailsByTransaction->get($transaction->id_transaksi, collect()) as $detail)
                                <span class="staff-product-line">{{ $detail->nama_produk }} × {{ $detail->jumlah }}</span>
                            @endforeach
                        </td>
                        <td>
                            <strong>{{ $transaction->metode_pembayaran === 'data_lama' ? 'Data lama' : ucwords(str_replace('_', ' ', $transaction->metode_pembayaran ?? '—')) }}</strong>
                            <small class="payment-status payment-status--{{ $transaction->status_pembayaran }}">
                                {{ $transaction->status_pembayaran === 'dikembalikan' ? 'Dikembalikan' : ucfirst($transaction->status_pembayaran) }}
                            </small>
                            <small>Rp {{ number_format($transaction->total_harga, 0, ',', '.') }}</small>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('cashier.transactions.status', $transaction->id_transaksi) }}" class="staff-status-form">
                                @csrf
                                @method('PUT')
                                <select name="status_pesanan" aria-label="Status pesanan #{{ $transaction->id_transaksi }}">
                                    @foreach ($nextStatuses as $nextStatus)
                                        <option value="{{ $nextStatus }}">{{ $statusLabels[$nextStatus] ?? $nextStatus }}</option>
                                    @endforeach
                                </select>
                                <button class="staff-button" type="submit" @disabled(count($nextStatuses) < 2)>Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="staff-empty">Belum ada transaksi dine-in.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="staff-pagination">{{ $dineInTransactions->links('pagination.coffee') }}</div>
</section>

<section class="staff-panel" aria-labelledby="takeaway-orders-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Bawa pulang</p>
            <h2 id="takeaway-orders-title">Transaksi take-away</h2>
        </div>
        <span class="staff-page-count">{{ $takeawayTransactions->total() }} pesanan</span>
    </div>
    <div class="staff-table-wrap">
        <table class="staff-table staff-transaction-table">
            <thead>
                <tr>
                    <th>ID pesanan</th><th>Nama pemesan</th><th>Produk dipesan</th><th>Hari / tanggal</th>
                    <th>Waktu terima pesanan</th><th>Status pemesanan</th><th>Metode</th><th>Kelola status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($takeawayTransactions as $transaction)
                    @php
                        $nextStatuses = array_merge(
                            [$transaction->status_pesanan],
                            $statusTransitions[$transaction->status_pesanan] ?? [],
                        );
                    @endphp
                    <tr>
                        <td><strong>#{{ $transaction->id_transaksi }}</strong></td>
                        <td>{{ $transaction->customer_name }}<small>{{ $transaction->username }}</small></td>
                        <td>
                            @foreach ($detailsByTransaction->get($transaction->id_transaksi, collect()) as $detail)
                                <span class="staff-product-line">{{ $detail->nama_produk }} × {{ $detail->jumlah }}</span>
                            @endforeach
                        </td>
                        <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal_transaksi)->translatedFormat('l, d M Y') }}</td>
                        <td>{{ $transaction->waktu_pengambilan ? \Illuminate\Support\Carbon::parse($transaction->waktu_pengambilan)->format('d M Y, H:i') : '—' }}</td>
                        <td>
                            <span class="payment-status payment-status--{{ $transaction->status_pembayaran }}">
                                {{ match ($transaction->status_pembayaran) {
                                    'berhasil' => 'Berhasil',
                                    'dikembalikan' => 'Dikembalikan',
                                    default => 'Gagal',
                                } }}
                            </span>
                        </td>
                        <td>{{ $transaction->metode_pembayaran === 'data_lama' ? 'Data lama' : ucwords(str_replace('_', ' ', $transaction->metode_pembayaran ?? '—')) }}</td>
                        <td>
                            <form method="POST" action="{{ route('cashier.transactions.status', $transaction->id_transaksi) }}" class="staff-status-form">
                                @csrf
                                @method('PUT')
                                <select name="status_pesanan" aria-label="Status pesanan #{{ $transaction->id_transaksi }}">
                                    @foreach ($nextStatuses as $nextStatus)
                                        <option value="{{ $nextStatus }}">{{ $statusLabels[$nextStatus] ?? $nextStatus }}</option>
                                    @endforeach
                                </select>
                                <button class="staff-button" type="submit" @disabled(count($nextStatuses) < 2)>Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="staff-empty">Belum ada transaksi take-away.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="staff-pagination">{{ $takeawayTransactions->links('pagination.coffee') }}</div>
</section>
@endsection
