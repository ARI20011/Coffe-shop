<aside class="menu-order-panel" aria-labelledby="menu-order-title">
    @auth
        @php
            $selectedTable = $availableTables->firstWhere('id_meja', (int) $selectedTableId);
        @endphp
        <div class="menu-order-panel__header">
            <div>
                <p class="eyebrow">Pesanan Anda</p>
                <h2 id="menu-order-title">{{ $orderType === 'dine_in' ? 'Dine-in' : 'Take away' }}</h2>
            </div>
            <span class="cart-count" aria-label="{{ count($orderItems) }} jenis menu">{{ count($orderItems) }}</span>
        </div>

        <div class="order-toggle" aria-label="Pilih jenis pesanan">
            <form method="POST" action="{{ route('cart.mode') }}">
                @csrf
                <input type="hidden" name="order_type" value="dine_in">
                <button type="submit" @class(['is-active' => $orderType === 'dine_in']) aria-pressed="{{ $orderType === 'dine_in' ? 'true' : 'false' }}">Dine-in</button>
            </form>
            <form method="POST" action="{{ route('cart.mode') }}">
                @csrf
                <input type="hidden" name="order_type" value="takeaway">
                <button type="submit" @class(['is-active' => $orderType === 'takeaway']) aria-pressed="{{ $orderType === 'takeaway' ? 'true' : 'false' }}">Take away</button>
            </form>
        </div>

        @if ($orderType === 'dine_in')
            <form class="dine-in-fields" method="POST" action="{{ route('cart.mode') }}">
                @csrf
                <input type="hidden" name="order_type" value="dine_in">
                <label for="dine-in-table">Pilih meja</label>
                <select id="dine-in-table" name="table_id" required>
                    <option value="">Pilih meja</option>
                    @foreach ($availableTables as $table)
                        <option value="{{ $table->id_meja }}" @selected((int) $selectedTableId === (int) $table->id_meja)>
                            {{ $table->nomor_meja }}
                        </option>
                    @endforeach
                </select>
                <button class="button button--profile-outline" type="submit">Pakai meja</button>
                @if ($availableTables->isEmpty())
                    <p class="menu-order-panel__empty">Semua meja sedang dipesan.</p>
                @endif
                @error('table_id')
                    <p class="form-notice form-notice--error" role="alert">{{ $message }}</p>
                @enderror
            </form>
        @endif

        @if (count($orderItems) > 0)
            <div class="menu-order-items">
                @foreach ($orderItems as $item)
                    <article class="menu-order-item">
                        <div>
                            <h3>{{ $item['nama_menu'] }}</h3>
                            <p>Rp {{ number_format($item['harga'], 0, ',', '.') }} × {{ $item['jumlah'] }}</p>
                        </div>
                        <div class="menu-order-item__actions">
                            <strong>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                            <div class="menu-order-item__quantity">
                                @foreach (['decrease' => '−', 'increase' => '+'] as $action => $label)
                                    <form method="POST" action="{{ route('cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                                        <input type="hidden" name="action" value="{{ $action }}">
                                        <input type="hidden" name="order_type" value="{{ $orderType }}">
                                        <button type="submit" aria-label="{{ $action === 'increase' ? 'Tambah' : 'Kurangi' }} {{ $item['nama_menu'] }}"
                                            @disabled($action === 'increase' && $item['jumlah'] >= $item['stok_realtime'])>{{ $label }}</button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('cart.update') }}">
                                    @csrf
                                    <input type="hidden" name="menu_id" value="{{ $item['id_menu'] }}">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="order_type" value="{{ $orderType }}">
                                    <button type="submit" class="menu-order-item__remove">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="menu-order-total">
                <span>Total</span>
                <strong>Rp {{ number_format($orderTotal, 0, ',', '.') }}</strong>
            </div>
            @if ($orderType === 'dine_in')
                <form class="menu-order-schedule" method="GET" action="{{ route('checkout.show') }}">
                    <input type="hidden" name="order_type" value="dine_in">
                    <input type="hidden" name="table_id" value="{{ $selectedTable?->id_meja }}">
                    <label for="order-arrival-at">Waktu tiba di lokasi</label>
                    <input id="order-arrival-at" type="datetime-local" name="arrival_at"
                        min="{{ now()->format('Y-m-d\TH:i') }}" required @disabled($selectedTable === null)>
                    <button class="button button--brown menu-order-checkout" type="submit" @disabled($selectedTable === null)>Lanjut checkout</button>
                </form>
            @else
                <form class="menu-order-schedule" method="GET" action="{{ route('checkout.show') }}">
                    <input type="hidden" name="order_type" value="takeaway">
                    <label for="order-pickup-at">Waktu pengambilan</label>
                    <input id="order-pickup-at" type="datetime-local" name="pickup_at"
                        min="{{ now()->format('Y-m-d\TH:i') }}" required>
                    <button class="button button--brown menu-order-checkout" type="submit">Lanjut checkout</button>
                </form>
                <a class="menu-order-panel__link menu-order-panel__cart-link" href="{{ route('cart.index') }}">Ubah jumlah di keranjang →</a>
            @endif
        @else
            <p class="menu-order-panel__empty">Belum ada menu yang dipilih. Tekan tombol + pada menu untuk menambah pesanan.</p>
            @if ($orderType === 'takeaway')
                <a class="menu-order-panel__link" href="{{ route('cart.index') }}">Buka keranjang take away →</a>
            @endif
        @endif
    @else
        <div class="menu-order-panel__guest">
            <p class="eyebrow">Pesanan Anda</p>
            <h2>Mulai pesan setelah login</h2>
            <p>Pilih menu, lalu masuk ke akun untuk menyimpan pesananmu.</p>
            <a class="button button--brown" href="{{ route('login', ['next' => 'home']) }}">Masuk untuk memesan</a>
        </div>
    @endauth
</aside>
