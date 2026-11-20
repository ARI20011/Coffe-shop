@extends('layouts.staff')

@section('title', 'Kelola produk')

@section('content')
<section class="staff-page-heading">
    <div>
        <p class="staff-eyebrow">Inventaris kedai</p>
        <h1>Kelola produk</h1>
        <p>Tambah menu, atur stok, dan perbarui informasi produk.</p>
    </div>
    <span class="staff-page-count">{{ number_format($products->total(), 0, ',', '.') }} produk</span>
</section>

<section class="staff-panel" aria-labelledby="create-product-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Menu baru</p>
            <h2 id="create-product-title">Tambah produk</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('cashier.products.store') }}" class="staff-product-form">
        @csrf
        <label>Nama produk<input name="nama_produk" value="{{ old('nama_produk') }}" maxlength="150" required></label>
        <label>Kategori<input name="kategori" value="{{ old('kategori') }}" maxlength="100" list="product-categories" placeholder="Contoh: Kopi"></label>
        <datalist id="product-categories">
            @foreach ($categories as $category)
                <option value="{{ $category }}"></option>
            @endforeach
        </datalist>
        <label>Harga (Rp)<input name="harga" type="number" min="0" value="{{ old('harga') }}" required></label>
        <label>Stok<input name="stok" type="number" min="0" max="1000000" value="{{ old('stok') }}" required></label>
        <label class="staff-product-form__wide">Deskripsi<textarea name="deskripsi" rows="2" maxlength="5000">{{ old('deskripsi') }}</textarea></label>
        <label class="staff-product-form__wide">URL/path gambar<input name="gambar" value="{{ old('gambar') }}" maxlength="2048" placeholder="resources/img/... atau https://..."></label>
        <button class="staff-button" type="submit">Simpan produk</button>
    </form>
</section>

<section class="staff-panel" aria-labelledby="product-list-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Daftar inventaris</p>
            <h2 id="product-list-title">Semua produk</h2>
        </div>
    </div>
    <form class="staff-filter-form" method="GET" action="{{ route('cashier.products.index') }}">
        <label class="sr-only" for="product-search">Cari produk</label>
        <input id="product-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama produk">
        <label class="sr-only" for="product-category-filter">Kategori</label>
        <select id="product-category-filter" name="kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(($filters['kategori'] ?? '') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <button class="staff-button" type="submit">Cari produk</button>
    </form>
    <div class="staff-table-wrap">
        <table class="staff-table">
            <thead>
                <tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->nama_produk }}</strong>
                            @if ($product->deskripsi)
                                <small>{{ $product->deskripsi }}</small>
                            @endif
                        </td>
                        <td>{{ $product->nama_kategori ?? '—' }}</td>
                        <td>Rp {{ number_format($product->harga, 0, ',', '.') }}</td>
                        <td>{{ number_format($product->stok, 0, ',', '.') }}</td>
                        <td class="staff-table__actions">
                            <button class="staff-button staff-button--outline" type="button" data-dialog-open="edit-product-{{ $product->id_produk }}">Edit</button>
                            <button class="staff-button staff-button--danger" type="button" data-dialog-open="delete-product-{{ $product->id_produk }}">Hapus</button>

                            <dialog class="staff-dialog" id="edit-product-{{ $product->id_produk }}" aria-labelledby="edit-title-{{ $product->id_produk }}">
                                <form method="POST" action="{{ route('cashier.products.update', $product->id_produk) }}" class="staff-dialog__content">
                                    @csrf
                                    @method('PUT')
                                    <div class="staff-dialog__heading">
                                        <div>
                                            <p class="staff-eyebrow">Produk #{{ $product->id_produk }}</p>
                                            <h2 id="edit-title-{{ $product->id_produk }}">Edit barang</h2>
                                        </div>
                                        <button class="staff-dialog__close" type="button" data-dialog-close aria-label="Tutup">×</button>
                                    </div>
                                    <label>Nama produk<input name="nama_produk" value="{{ $product->nama_produk }}" maxlength="150" required></label>
                                    <label>Kategori<input name="kategori" value="{{ $product->nama_kategori }}" maxlength="100" list="product-categories"></label>
                                    <label>Harga (Rp)<input name="harga" type="number" min="0" value="{{ $product->harga }}" required></label>
                                    <label>Stok<input name="stok" type="number" min="0" max="1000000" value="{{ $product->stok }}" required></label>
                                    <label>Deskripsi<textarea name="deskripsi" rows="3" maxlength="5000">{{ $product->deskripsi }}</textarea></label>
                                    <label>URL/path gambar<input name="gambar" value="{{ $product->gambar }}" maxlength="2048"></label>
                                    <div class="staff-dialog__actions">
                                        <button class="staff-button staff-button--outline" type="button" data-dialog-close>Batal</button>
                                        <button class="staff-button" type="submit">Simpan perubahan</button>
                                    </div>
                                </form>
                            </dialog>

                            <dialog class="staff-dialog staff-dialog--confirm" id="delete-product-{{ $product->id_produk }}" aria-labelledby="delete-title-{{ $product->id_produk }}">
                                <form method="POST" action="{{ route('cashier.products.destroy', $product->id_produk) }}" class="staff-dialog__content">
                                    @csrf
                                    @method('DELETE')
                                    <div class="staff-dialog__heading">
                                        <div>
                                            <p class="staff-eyebrow">Konfirmasi penghapusan</p>
                                            <h2 id="delete-title-{{ $product->id_produk }}">Hapus barang?</h2>
                                        </div>
                                        <button class="staff-dialog__close" type="button" data-dialog-close aria-label="Tutup">×</button>
                                    </div>
                                    <p>Produk <strong>{{ $product->nama_produk }}</strong> akan dihapus dari katalog.</p>
                                    <div class="staff-dialog__actions">
                                        <button class="staff-button staff-button--outline" type="button" data-dialog-close>Batal</button>
                                        <button class="staff-button staff-button--danger" type="submit">Ya, hapus barang</button>
                                    </div>
                                </form>
                            </dialog>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Belum ada produk. Tambahkan produk pertama melalui formulir di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="staff-pagination">{{ $products->links('pagination.coffee') }}</div>
</section>
@endsection
