<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (in_array($user?->role, ['admin', 'kasir'], true)) {
            return redirect()->route('staff.profile');
        }

        $transactions = collect();
        $detailsByTransaction = collect();

        if ($user !== null) {
            $transactions = DB::table('transaksi')
                ->where('id_pelanggan', $user->getKey())
                ->where('tanggal_transaksi', '>=', now()->subDays(30))
                ->orderByDesc('tanggal_transaksi')
                ->get();

            if ($transactions->isNotEmpty()) {
                $detailsByTransaction = DB::table('detail_transaksi')
                    ->whereIn('id_transaksi', $transactions->pluck('id_transaksi'))
                    ->get()
                    ->groupBy('id_transaksi');
            }
        }

        return view('profile.show', [
            'user' => $user,
            'transactions' => $transactions,
            'detailsByTransaction' => $detailsByTransaction,
        ]);
    }

    public function staff(Request $request): View
    {
        return view('profile.staff', ['user' => $request->user()]);
    }
}
