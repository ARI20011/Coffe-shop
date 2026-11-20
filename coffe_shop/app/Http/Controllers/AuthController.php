<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function showAdminLogin(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
            'next' => ['sometimes', 'in:home'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'pelanggan')
            ->first();

        if ($user !== null && ! $user->akun_aktif) {
            return back()
                ->withErrors(['email' => 'Akun ini sedang dinonaktifkan. Hubungi admin untuk bantuan.'])
                ->onlyInput('email');
        }

        if ($user?->login_locked_until !== null && Carbon::parse($user->login_locked_until)->isFuture()) {
            $minutes = max(1, (int) ceil((Carbon::parse($user->login_locked_until)->getTimestamp() - now()->getTimestamp()) / 60));

            return back()
                ->withErrors(['email' => "Akun dikunci setelah 3 percobaan gagal. Coba lagi dalam {$minutes} menit."])
                ->onlyInput('email');
        }

        if (! $request->boolean('captcha')) {
            $attempts = $user === null ? 0 : $this->recordCustomerLoginFailure((int) $user->getKey());

            return back()
                ->withErrors($attempts >= 3
                    ? ['email' => 'Akun dikunci selama 15 menit setelah 3 percobaan gagal.']
                    : ['captcha' => 'Centang verifikasi "I\'m not a robot" sebelum masuk.'])
                ->onlyInput('email');
        }

        $storedPassword = $user?->getRawOriginal('password');
        $legacyPlaintext = $storedPassword !== null
            && password_get_info($storedPassword)['algoName'] === 'unknown';
        $passwordMatches = $user !== null && ($legacyPlaintext
            ? hash_equals($storedPassword, $credentials['password'])
            : Hash::check($credentials['password'], $storedPassword));

        if (! $passwordMatches) {
            $attempts = $user === null ? 0 : $this->recordCustomerLoginFailure((int) $user->getKey());

            return back()
                ->withErrors($attempts >= 3
                    ? ['email' => 'Akun dikunci selama 15 menit setelah 3 percobaan gagal.']
                    : ['email' => 'Email atau kata sandi tidak sesuai.'])
                ->onlyInput('email');
        }

        $user->forceFill([
            'password' => $legacyPlaintext ? $credentials['password'] : $storedPassword,
            'last_login_at' => now(),
            'login_failed_attempts' => 0,
            'login_locked_until' => null,
        ])->save();
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->input('next') === 'home') {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard.index', ['page' => 'home']);
        }

        return redirect()->intended(route('dashboard.index', ['page' => 'home']));
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $rateLimitKey = hash('sha256', 'staff|'.Str::lower($credentials['username']).'|'.$request->ip());
        $failedAttemptsKey = 'staff-login:attempts:'.$rateLimitKey;
        $lockKey = 'staff-login:lock:'.$rateLimitKey;

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $waitSeconds = RateLimiter::availableIn($lockKey);

            return back()
                ->withErrors(['username' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$waitSeconds} detik."])
                ->onlyInput('username');
        }

        $staffIdentity = Str::lower($credentials['username']);
        $user = User::query()
            ->where(function ($query) use ($staffIdentity): void {
                $query->whereRaw('LOWER(username) = ?', [$staffIdentity])
                    ->orWhereRaw('LOWER(name) = ?', [$staffIdentity]);
            })
            ->whereIn('role', ['admin', 'kasir'])
            ->where('akun_aktif', true)
            ->first();

        $storedPassword = $user?->getRawOriginal('password');
        $legacyPlaintext = $storedPassword !== null
            && password_get_info($storedPassword)['algoName'] === 'unknown';
        $passwordMatches = $user !== null && ($legacyPlaintext
            ? hash_equals($storedPassword, $credentials['password'])
            : Hash::check($credentials['password'], $storedPassword));

        if (! $passwordMatches) {
            RateLimiter::hit($failedAttemptsKey, 60);

            if (RateLimiter::attempts($failedAttemptsKey) >= 3) {
                RateLimiter::clear($failedAttemptsKey);
                RateLimiter::hit($lockKey, 15);

                return back()
                    ->withErrors(['username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 detik.'])
                    ->onlyInput('username');
            }

            return back()
                ->withErrors(['username' => 'Username atau kata sandi tidak sesuai.'])
                ->onlyInput('username');
        }

        RateLimiter::clear($failedAttemptsKey);
        RateLimiter::clear($lockKey);
        Auth::login($user);
        $user->forceFill([
            'password' => $legacyPlaintext ? $credentials['password'] : $storedPassword,
            'last_login_at' => now(),
        ])->save();
        $request->session()->regenerate();

        return redirect()->route('dashboard.index', ['page' => $user->role]);
    }

    public function register(Request $request, VoucherService $vouchers): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => ['nullable', 'string', 'max:16', 'exists:users,referral_code'],
            'terms' => ['accepted'],
            'captcha' => ['accepted'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username ini sudah digunakan.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi harus terdiri dari minimal 8 karakter.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
            'captcha.accepted' => 'Centang verifikasi "I\'m not a robot" sebelum mendaftar.',
        ]);

        $referrerId = isset($validated['referral_code'])
            ? DB::table('users')->where('referral_code', mb_strtoupper($validated['referral_code']))->value('id')
            : null;
        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'pelanggan',
            'saldo' => 50000,
            'referred_by' => $referrerId,
        ]);
        $vouchers->referralCodeFor($user);

        return redirect()
            ->route('login')
            ->with('status', 'Akun berhasil dibuat. Silakan masuk menggunakan email dan kata sandi yang baru didaftarkan.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }

    private function recordCustomerLoginFailure(int $userId): int
    {
        return DB::transaction(function () use ($userId): int {
            $user = DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first(['login_failed_attempts', 'login_locked_until', 'akun_aktif']);

            if ($user === null || ! $user->akun_aktif) {
                return 0;
            }

            $lockedUntil = $user->login_locked_until;
            if ($lockedUntil !== null && Carbon::parse($lockedUntil)->isFuture()) {
                return (int) $user->login_failed_attempts;
            }

            $attempts = $lockedUntil === null ? (int) $user->login_failed_attempts : 0;
            $attempts++;
            DB::table('users')->where('id', $userId)->update([
                'login_failed_attempts' => $attempts,
                'login_locked_until' => $attempts >= 3 ? now()->addMinutes(15) : null,
                'updated_at' => now(),
            ]);

            return $attempts;
        });
    }
}
