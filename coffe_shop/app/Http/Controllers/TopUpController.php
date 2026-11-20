<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TopUpController extends Controller
{
    private const METHODS = [
        'dana' => 'DANA',
        'qris' => 'QRIS',
        'gopay' => 'GoPay',
    ];

    public function index(Request $request): View
    {
        return view('wallet.topup', [
            'saldo' => (int) $request->user()->saldo,
            'methods' => self::METHODS,
            'topups' => DB::table('top_up')
                ->where('id_pelanggan', $request->user()->getKey())
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:10000', 'max:5000000'],
            'metode_pembayaran' => ['required', Rule::in(array_keys(self::METHODS))],
        ], [
            'jumlah.min' => 'Nominal top up minimal Rp 10.000.',
            'jumlah.max' => 'Nominal top up maksimal Rp 5.000.000.',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $userId = $request->user()->getKey();
            $customer = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id']);
            abort_if($customer === null, 404);

            DB::table('top_up')->insert([
                'id_pelanggan' => $userId,
                'jumlah' => $validated['jumlah'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'status_topup' => 'berhasil',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')
                ->where('id', $userId)
                ->increment('saldo', $validated['jumlah'], ['updated_at' => now()]);
        });

        return redirect()
            ->route('profile.show')
            ->with('status', 'Top up simulasi berhasil. Saldo Coffee Shop sudah diperbarui.');
    }
}
