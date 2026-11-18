@extends('layouts.app')

@section('title', 'Katalog Menu')

@section('content')
<div
    x-data="{
        tipePesanan: 'Take Away',
        kategori: @js($kategoriAktif),
        keranjang: [],
        tambah(item) {
            if (item.stok <= 0) return;
            const ada = this.keranjang.find(row => row.id_menu === item.id_menu);
            if (ada) ada.jumlah++;
            else this.keranjang.push({ ...item, jumlah: 1 });
        },
        kurang(item) {
            const ada = this.keranjang.find(row => row.id_menu === item.id_menu);
            if (!ada) return;
            ada.jumlah--;
            if (ada.jumlah <= 0) this.keranjang = this.keranjang.filter(row => row.id_menu !== item.id_menu);
        },
        get total() {
            return this.keranjang.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
        }
    }"
    class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]"
>
    <main>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-stone-500">Kopi Kala Aira</p>
                <h1 class="mt-1 text-3xl font-bold text-stone-950">Katalog Menu</h1>
            </div>

            <div class="flex rounded-xl border border-stone-300 bg-white p-1">
                <button type="button"
                    @click="tipePesanan = 'Take Away'"
                    :class="tipePesanan === 'Take Away' ? 'bg-stone-950 text-white' : 'text-stone-600'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold">
                    Take Away
                </button>
                <button type="button"
                    @click="tipePesanan = 'Dine-in'"
                    :class="tipePesanan === 'Dine-in' ? 'bg-stone-950 text-white' : 'text-stone-600'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold">
                    Dine-in
                </button>
            </div>
        </div>

        <div x-show="tipePesanan === 'Dine-in'" x-cloak class="mb-5 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 sm:grid-cols-2">
            <label class="text-sm font-semibold text-stone-700">
                Meja
                <select class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2.5">
                    <option>Pilih meja</option>
                    <option>Meja 01</option>
                    <option>Meja 02</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-stone-700">
                Jam datang
                <input type="time" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2.5">
            </label>
        </div>

        <nav class="mb-5 flex gap-2 overflow-x-auto" aria-label="Kategori menu">
            @foreach (['Semua', 'Kopi', 'Non-Kopi', 'Camilan'] as $namaKategori)
                <a href="{{ route('catalog.index', ['kategori' => $namaKategori]) }}"
                    class="{{ $kategoriAktif === $namaKategori ? 'bg-stone-950 text-white' : 'border border-stone-300 bg-white text-stone-700' }} shrink-0 rounded-full px-4 py-2 text-sm font-semibold">
                    {{ $namaKategori }}
                </a>
            @endforeach
        </nav>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($menu as $item)
                <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
                    <div class="relative h-44 bg-stone-200">
                        <img src="{{ $item['foto_url'] }}" alt="{{ $item['nama_menu'] }}"
                            class="h-full w-full object-cover {{ $item['stok_realtime'] === 0 ? 'grayscale' : '' }}">
                        @if ($item['stok_realtime'] === 0)
                            <span class="absolute left-3 top-3 rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">Habis</span>
                        @elseif ($item['stok_realtime'] <= 5)
                            <span class="absolute left-3 top-3 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">Stok Menipis</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">{{ $item['kategori'] }}</p>
                        <h2 class="mt-1 text-lg font-bold text-stone-950">{{ $item['nama_menu'] }}</h2>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-bold text-stone-950">Rp {{ number_format($item['harga'], 0, ',', '.') }}</p>
                                <p class="text-xs text-stone-500">Sisa {{ $item['stok_realtime'] }}</p>
                            </div>
                            <button type="button"
                                @click='tambah(@js($item))'
                                @disabled($item['stok_realtime'] === 0)
                                class="rounded-lg bg-stone-950 px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">
                                Tambah
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
                    <p class="text-lg font-bold text-stone-900">Menu tidak tersedia</p>
                    <p class="mt-1 text-sm text-stone-500">Belum ada menu pada kategori ini. Silakan pilih kategori lain.</p>
                </div>
            @endforelse
        </div>
    </main>

    <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-5 lg:sticky lg:top-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-stone-950">Keranjang</h2>
            <span x-text="keranjang.reduce((sum, item) => sum + item.jumlah, 0)" class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-bold"></span>
        </div>

        <div class="mt-4 space-y-3">
            <template x-if="keranjang.length === 0">
                <p class="rounded-xl bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">Keranjang masih kosong</p>
            </template>
            <template x-for="item in keranjang" :key="item.id_menu">
                <div class="flex items-start justify-between gap-3 border-b border-stone-100 pb-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold" x-text="item.nama_menu"></p>
                        <p class="mt-1 text-xs text-stone-500" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(item.harga)"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="kurang(item)" class="h-7 w-7 rounded-md border border-stone-300">−</button>
                        <span class="w-5 text-center text-sm font-bold" x-text="item.jumlah"></span>
                        <button type="button" @click="tambah(item)" :disabled="item.jumlah >= item.stok" class="h-7 w-7 rounded-md bg-stone-950 text-white disabled:bg-stone-300">+</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 flex items-center justify-between border-t border-stone-200 pt-4">
            <span class="font-semibold text-stone-600">Total</span>
            <strong class="text-xl" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(total)"></strong>
        </div>
        <button type="button" :disabled="keranjang.length === 0"
            class="mt-4 w-full rounded-lg bg-stone-950 px-4 py-3 font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">
            Checkout
        </button>
    </aside>
</div>
@endsection
