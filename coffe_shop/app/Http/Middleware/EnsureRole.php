<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, $roles, true), 403);

        if (! $user->akun_aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $field = $user->role === 'pelanggan' ? 'email' : 'username';

            return redirect()
                ->route($user->role === 'pelanggan' ? 'login' : 'admin.login')
                ->withErrors([$field => 'Akun ini telah dinonaktifkan. Hubungi admin untuk bantuan.']);
        }

        return $next($request);
    }
}
