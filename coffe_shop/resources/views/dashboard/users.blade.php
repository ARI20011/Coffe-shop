@extends('layouts.staff')

@section('title', 'Kelola pengguna')

@section('content')
<section class="staff-page-heading">
    <div>
        <p class="staff-eyebrow">Akses akun</p>
        <h1>Kelola pengguna</h1>
        <p>Kelola pelanggan dan staf, pantau akun yang terkunci, atau buka blokir login.</p>
    </div>
    <span class="staff-page-count">{{ number_format($users->total(), 0, ',', '.') }} akun</span>
</section>

<section class="staff-panel" aria-labelledby="create-user-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Akun baru</p>
            <h2 id="create-user-title">Tambah pengguna</h2>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.users.store') }}" class="staff-product-form">
        @csrf
        <label>Nama lengkap<input name="name" value="{{ old('name') }}" maxlength="255" required></label>
        <label>Username<input name="username" value="{{ old('username') }}" maxlength="100" required></label>
        <label>Email<input name="email" type="email" value="{{ old('email') }}" maxlength="255" required></label>
        <label>Role<select name="role" required>@foreach ($roles as $role)<option value="{{ $role }}" @selected(old('role', 'pelanggan') === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label>
        <label>Kata sandi<input name="password" type="password" minlength="8" required></label>
        <label>Konfirmasi kata sandi<input name="password_confirmation" type="password" minlength="8" required></label>
        <button class="staff-button" type="submit">Buat akun</button>
    </form>
</section>

<section class="staff-panel" aria-labelledby="users-list-title">
    <div class="staff-section-heading">
        <div>
            <p class="staff-eyebrow">Direktori</p>
            <h2 id="users-list-title">Daftar akun</h2>
        </div>
    </div>
    <form class="staff-filter-form" method="GET" action="{{ route('admin.users.index') }}">
        <label class="sr-only" for="user-search">Cari pengguna</label>
        <input id="user-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama, username, atau email">
        <label class="sr-only" for="user-role-filter">Filter role</label>
        <select id="user-role-filter" name="role">
            <option value="">Semua role</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="user-status-filter">Filter status akun</label>
        <select id="user-status-filter" name="status">
            <option value="">Semua status</option>
            <option value="aktif" @selected(($filters['status'] ?? '') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(($filters['status'] ?? '') === 'nonaktif')>Nonaktif</option>
            <option value="terkunci" @selected(($filters['status'] ?? '') === 'terkunci')>Terkunci</option>
        </select>
        <button class="staff-button" type="submit">Terapkan</button>
    </form>

    <div class="staff-table-wrap">
        <table class="staff-table">
            <thead><tr><th>Pengguna</th><th>Role</th><th>Status</th><th>Terakhir masuk</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $account)
                    @php($isLocked = $account->login_locked_until && \Illuminate\Support\Carbon::parse($account->login_locked_until)->isFuture())
                    <tr>
                        <td><strong>{{ $account->name }}</strong><small>{{ $account->username }} · {{ $account->email }}</small></td>
                        <td>{{ ucfirst($account->role) }}</td>
                        <td>
                            @if (! $account->akun_aktif)
                                Nonaktif
                            @elseif ($isLocked)
                                Terkunci sampai {{ \Illuminate\Support\Carbon::parse($account->login_locked_until)->format('d M, H:i') }}
                            @else
                                Aktif
                            @endif
                        </td>
                        <td>{{ $account->last_login_at ? \Illuminate\Support\Carbon::parse($account->last_login_at)->format('d M Y, H:i') : 'Belum pernah masuk' }}</td>
                        <td class="staff-table__actions">
                            <button class="staff-button staff-button--outline" type="button" data-dialog-open="edit-user-{{ $account->id }}">Edit</button>
                            @if ($isLocked)
                                <form method="POST" action="{{ route('admin.users.unlock', $account->id) }}">
                                    @csrf
                                    <button class="staff-button staff-button--outline" type="submit">Buka blokir</button>
                                </form>
                            @endif
                            @if ($account->akun_aktif && $account->id !== auth()->id())
                                <button class="staff-button staff-button--danger" type="button" data-dialog-open="delete-user-{{ $account->id }}">Nonaktifkan</button>
                            @endif

                            <dialog class="staff-dialog" id="edit-user-{{ $account->id }}" aria-labelledby="edit-user-title-{{ $account->id }}">
                                <form method="POST" action="{{ route('admin.users.update', $account->id) }}" class="staff-dialog__content">
                                    @csrf
                                    @method('PUT')
                                    <div class="staff-dialog__heading">
                                        <div><p class="staff-eyebrow">Akun #{{ $account->id }}</p><h2 id="edit-user-title-{{ $account->id }}">Edit pengguna</h2></div>
                                        <button class="staff-dialog__close" type="button" data-dialog-close aria-label="Tutup">×</button>
                                    </div>
                                    <label>Nama lengkap<input name="name" value="{{ $account->name }}" maxlength="255" required></label>
                                    <label>Username<input name="username" value="{{ $account->username }}" maxlength="100" required></label>
                                    <label>Email<input name="email" type="email" value="{{ $account->email }}" maxlength="255" required></label>
                                    <label>Role<select name="role" required>@foreach ($roles as $role)<option value="{{ $role }}" @selected($account->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label>
                                    <input type="hidden" name="akun_aktif" value="0">
                                    <label class="remember-option"><input name="akun_aktif" type="checkbox" value="1" @checked($account->akun_aktif)><span>Akun aktif</span></label>
                                    <label>Kata sandi baru (opsional)<input name="password" type="password" minlength="8" autocomplete="new-password"></label>
                                    <label>Konfirmasi kata sandi<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
                                    <div class="staff-dialog__actions">
                                        <button class="staff-button staff-button--outline" type="button" data-dialog-close>Batal</button>
                                        <button class="staff-button" type="submit">Simpan perubahan</button>
                                    </div>
                                </form>
                            </dialog>

                            <dialog class="staff-dialog staff-dialog--confirm" id="delete-user-{{ $account->id }}" aria-labelledby="delete-user-title-{{ $account->id }}">
                                <form method="POST" action="{{ route('admin.users.destroy', $account->id) }}" class="staff-dialog__content">
                                    @csrf
                                    @method('DELETE')
                                    <div class="staff-dialog__heading">
                                        <div><p class="staff-eyebrow">Konfirmasi akun</p><h2 id="delete-user-title-{{ $account->id }}">Nonaktifkan akun?</h2></div>
                                        <button class="staff-dialog__close" type="button" data-dialog-close aria-label="Tutup">×</button>
                                    </div>
                                    <p>Akun <strong>{{ $account->username }}</strong> tidak dapat masuk lagi. Riwayat transaksi tetap dipertahankan.</p>
                                    <div class="staff-dialog__actions">
                                        <button class="staff-button staff-button--outline" type="button" data-dialog-close>Batal</button>
                                        <button class="staff-button staff-button--danger" type="submit">Nonaktifkan akun</button>
                                    </div>
                                </form>
                            </dialog>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="staff-empty">Tidak ada akun yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="staff-pagination">{{ $users->links('pagination.coffee') }}</div>
</section>
@endsection
