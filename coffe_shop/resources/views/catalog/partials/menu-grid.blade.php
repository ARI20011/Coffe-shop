<div class="menu-grid">
    @forelse ($menu as $item)
        @php
            $photoUrl = $item['foto_url'];
            if (str_starts_with($photoUrl, 'resources/')) {
                $photoUrl = Vite::asset($photoUrl);
            } elseif (! str_starts_with($photoUrl, 'https://') && ! str_starts_with($photoUrl, 'http://')) {
                $photoUrl = asset($photoUrl);
            }
        @endphp
        <article class="menu-card" id="menu-produk-{{ $item['id_menu'] }}">
            <div @class(['menu-card__photo', 'menu-card__photo--feature' => $item['id_menu'] === 1])>
                <img
                    src="{{ $photoUrl }}"
                    alt="{{ $item['nama_menu'] }}"
                    loading="lazy"
                    class="{{ $item['stok_realtime'] === 0 ? 'is-sold-out' : '' }}"
                >
                <span class="menu-card__tag">{{ $item['kategori'] }}</span>
                @if (in_array($item['id_menu'], $bestSellerIds, true))
                    <span class="menu-card__best-seller">Terlaris</span>
                @endif
                @if ($item['stok_realtime'] === 0)
                    <span class="stock-badge stock-badge--empty">Habis</span>
                @elseif ($item['stok_realtime'] <= 5)
                    <span class="stock-badge">Stok menipis</span>
                @endif
            </div>
            <div class="menu-card__body">
                <div>
                    <p class="menu-card__category">{{ $item['kategori'] }}</p>
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
            <strong>Menu tidak ditemukan</strong>
            <p>Coba kata kunci atau kategori yang berbeda.</p>
        </div>
    @endforelse
</div>
