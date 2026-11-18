<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $transactions = collect();
        $detailsByTransaction = collect();

        if ($user !== null) {
            $transactions = DB::table('transaksi')
                ->where('id_pelanggan', $user->getKey())
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
}
