<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    private const ROLES = ['pelanggan', 'kasir', 'admin'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', Rule::in(self::ROLES)],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif', 'terkunci'])],
        ]);
        $query = DB::table('users')->select([
            'id',
            'name',
            'username',
            'email',
            'role',
            'akun_aktif',
            'login_failed_attempts',
            'login_locked_until',
            'last_login_at',
            'created_at',
        ]);

        if (($filters['q'] ?? '') !== '') {
            $needle = '%'.$filters['q'].'%';
            $query->where(function ($builder) use ($needle): void {
                $builder->where('name', 'like', $needle)
                    ->orWhere('username', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        if (($filters['role'] ?? '') !== '') {
            $query->where('role', $filters['role']);
        }

        match ($filters['status'] ?? '') {
            'aktif' => $query->where('akun_aktif', true),
            'nonaktif' => $query->where('akun_aktif', false),
            'terkunci' => $query->where('login_locked_until', '>', now()),
            default => null,
        };

        return view('dashboard.users', [
            'users' => $query->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'roles' => self::ROLES,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
        ]);

        DB::table('users')->insert([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'saldo' => 0,
            'akun_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function update(Request $request, int $userId): RedirectResponse
    {
        $user = DB::table('users')->where('id', $userId)->first();
        abort_if($user === null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(self::ROLES)],
            'akun_aktif' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', 'string', 'min:8'],
        ]);

        if ((int) $request->user()->getKey() === $userId
            && ($validated['role'] !== 'admin' || ! (bool) $validated['akun_aktif'])) {
            throw ValidationException::withMessages([
                'role' => 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan atau diturunkan rolenya.',
            ]);
        }

        $changes = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'akun_aktif' => (bool) $validated['akun_aktif'],
            'updated_at' => now(),
        ];
        if (($validated['password'] ?? '') !== '') {
            $changes['password'] = Hash::make($validated['password']);
        }

        DB::table('users')->where('id', $userId)->update($changes);

        return redirect()->route('admin.users.index')->with('status', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, int $userId): RedirectResponse
    {
        abort_if((int) $request->user()->getKey() === $userId, 403, 'Akun admin yang sedang digunakan tidak dapat dihapus.');

        $changed = DB::table('users')->where('id', $userId)->update([
            'akun_aktif' => false,
            'login_failed_attempts' => 0,
            'login_locked_until' => null,
            'updated_at' => now(),
        ]);
        abort_if($changed === 0, 404);

        return redirect()->route('admin.users.index')->with('status', 'Akun dinonaktifkan; riwayat transaksinya tetap disimpan.');
    }

    public function unlock(int $userId): RedirectResponse
    {
        $changed = DB::table('users')
            ->where('id', $userId)
            ->where('login_locked_until', '>', now())
            ->update([
                'login_failed_attempts' => 0,
                'login_locked_until' => null,
                'updated_at' => now(),
            ]);
        abort_if($changed === 0, 404);

        return redirect()->route('admin.users.index')->with('status', 'Kunci login pengguna berhasil dibuka.');
    }
}
