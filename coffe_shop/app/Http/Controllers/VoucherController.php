<?php

namespace App\Http\Controllers;

use App\Support\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $referralCode = $this->vouchers->referralCodeFor($user);
        $promo = DB::table('vouchers')->where('code', 'KALA30')->first();
        $promoClaimCount = $promo === null
            ? 0
            : DB::table('voucher_pelanggan')
                ->where('id_voucher', $promo->id_voucher)
                ->where('sumber', 'kode')
                ->count();

        return view('vouchers.index', [
            'saldo' => (int) $user->saldo,
            'tasks' => $this->vouchers->tasksFor((int) $user->getKey()),
            'catalog' => $this->vouchers->catalogFor((int) $user->getKey()),
            'referralCode' => $referralCode,
            'referralUrl' => route('register', ['ref' => $referralCode]),
            'promoClaimsLeft' => max(0, (int) ($promo->batas_klaim ?? 0) - $promoClaimCount),
            'promoClaimed' => $promo !== null && DB::table('voucher_pelanggan')
                ->where('id_pelanggan', $user->getKey())
                ->where('id_voucher', $promo->id_voucher)
                ->exists(),
        ]);
    }

    public function mine(Request $request): View
    {
        return view('vouchers.mine', [
            'vouchers' => $this->vouchers->ownedBy((int) $request->user()->getKey()),
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ], [
            'code.required' => 'Masukkan kode voucher.',
        ]);

        $name = $this->vouchers->redeemCode((int) $request->user()->getKey(), $validated['code']);

        return redirect()->route('vouchers.mine')->with('status', "Voucher {$name} berhasil ditambahkan.");
    }

    public function claimTask(Request $request, string $task): RedirectResponse
    {
        $name = $this->vouchers->claimTask((int) $request->user()->getKey(), $task);

        return redirect()->route('vouchers.mine')->with('status', "Hadiah {$name} berhasil diklaim.");
    }

    public function purchase(Request $request, int $voucherId): RedirectResponse
    {
        $userId = (int) $request->user()->getKey();

        $name = DB::transaction(function () use ($userId, $voucherId): string {
            $voucher = DB::table('vouchers')->where('id_voucher', $voucherId)->lockForUpdate()->first();
            abort_if($voucher === null || ! $voucher->aktif, 404);

            if ((int) $voucher->harga_beli < 1) {
                abort(404);
            }

            if (DB::table('voucher_pelanggan')
                ->where('id_pelanggan', $userId)
                ->where('id_voucher', $voucherId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher ini sudah ada di Voucher Saya.',
                ]);
            }

            $customer = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id', 'saldo']);
            abort_if($customer === null, 404);

            if ((int) $customer->saldo < (int) $voucher->harga_beli) {
                throw ValidationException::withMessages([
                    'voucher' => 'Saldo Coffee Shop tidak cukup untuk membeli voucher ini.',
                ]);
            }

            DB::table('users')->where('id', $userId)->decrement(
                'saldo',
                (int) $voucher->harga_beli,
                ['updated_at' => now()],
            );
            $this->vouchers->issue($userId, $voucherId, 'beli');

            return $voucher->nama_voucher;
        });

        return redirect()->route('vouchers.mine')->with('status', "Voucher {$name} berhasil dibeli.");
    }
}
