@extends('layouts.staff')

@section('title', 'Profil staf')

@section('content')
<section class="staff-page-heading staff-page-heading--hero">
    <div>
        <p class="staff-eyebrow">Informasi akun</p>
        <h1>Profil {{ ucfirst($user->role) }}</h1>
        <p>Detail akun yang sedang digunakan untuk mengelola kedai.</p>
    </div>
</section>

<section class="staff-panel staff-profile-panel" aria-labelledby="staff-profile-title">
    <div class="staff-profile-panel__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
    <div>
        <h2 id="staff-profile-title">{{ $user->name }}</h2>
        <dl class="staff-profile-details">
            <div><dt>Username</dt><dd>{{ $user->username ?: '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
            <div><dt>Login terakhir</dt><dd>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum pernah login' }}</dd></div>
            <div><dt>Bergabung sejak</dt><dd>{{ $user->created_at?->format('d M Y') ?? 'Tanggal tidak tersedia' }}</dd></div>
        </dl>
    </div>
</section>
@endsection
