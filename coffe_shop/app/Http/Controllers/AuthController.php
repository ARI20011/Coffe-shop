<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $rateLimitKey = hash('sha256', Str::lower($credentials['email']).'|'.$request->ip());
        $failedAttemptsKey = 'login:attempts:'.$rateLimitKey;
        $lockKey = 'login:lock:'.$rateLimitKey;

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $waitSeconds = RateLimiter::availableIn($lockKey);

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$waitSeconds} detik."])
                ->onlyInput('email');
        }

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($failedAttemptsKey, 60);

            if (RateLimiter::attempts($failedAttemptsKey) >= 3) {
                RateLimiter::clear($failedAttemptsKey);
                RateLimiter::hit($lockKey, 15);

                return back()
                    ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 detik.'])
                    ->onlyInput('email');
            }

            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($failedAttemptsKey);
        RateLimiter::clear($lockKey);
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        if ($request->input('next') === 'home') {
            $request->session()->forget('url.intended');

            return redirect()->route('catalog.index');
        }

        return redirect()->intended(route('catalog.index'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
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

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'pelanggan',
        ]);

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
}
