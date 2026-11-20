<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    private const ORDER_STATUSES = ['paid', 'shipped', 'completed'];

    /**
     * @return list<array{id: string, title: string, description: string, progress: int, goal: int, reward: object|null, claimed: bool}>
     */
    public function tasksFor(int $userId): array
    {
        $progress = [
            'topup' => DB::table('top_up')
                ->where('id_pelanggan', $userId)
                ->where('status_topup', 'berhasil')
                ->where('jumlah', '>=', 50000)
                ->exists() ? 1 : 0,
            'orders' => (int) DB::table('detail_transaksi as details')
                ->join('transaksi', 'transaksi.id_transaksi', '=', 'details.id_transaksi')
                ->join('produk', 'produk.id_produk', '=', 'details.id_produk')
                ->join('kategori', 'kategori.id_kategori', '=', 'produk.id_kategori')
                ->where('transaksi.id_pelanggan', $userId)
                ->where('transaksi.status_pembayaran', 'berhasil')
                ->whereIn('transaksi.status_pesanan', self::ORDER_STATUSES)
                ->whereIn('kategori.nama_kategori', ['Kopi', 'Non-Kopi', 'Camilan'])
                ->sum('details.jumlah'),
            'referrals' => (int) DB::table('users')->where('referred_by', $userId)->count(),
        ];

        $tasks = [
            [
                'id' => 'topup',
                'title' => 'Top up saldo minimal Rp50.000',
                'description' => 'Lakukan top up saldo Coffee Shop minimal Rp50.000 untuk mendapat cashback.',
                'goal' => 1,
                'reward_code' => 'TOPUP20',
            ],
            [
                'id' => 'orders',
                'title' => 'Pesan 5 produk minuman atau pastry',
                'description' => 'Selesaikan pesanan berisi produk kategori Kopi, Non-Kopi, atau Camilan.',
                'goal' => 5,
                'reward_code' => 'LATTE-FREE',
            ],
            [
                'id' => 'referrals',
                'title' => 'Ajak dan bagikan ke 5 teman',
                'description' => 'Bagikan kode referral; setiap teman harus mendaftar melalui tautanmu.',
                'goal' => 5,
                'reward_code' => 'REFERRAL35',
            ],
        ];

        return array_map(function (array $task) use ($progress, $userId): array {
            $voucher = DB::table('vouchers')->where('code', $task['reward_code'])->first();

            return [
                'id' => $task['id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'progress' => min($progress[$task['id']], $task['goal']),
                'goal' => $task['goal'],
                'reward' => $voucher,
                'claimed' => $voucher !== null && DB::table('voucher_pelanggan')
                    ->where('id_pelanggan', $userId)
                    ->where('id_voucher', $voucher->id_voucher)
                    ->exists(),
            ];
        }, $tasks);
    }

    /**
     * @return Collection<int, object>
     */
    public function catalogFor(int $userId): Collection
    {
        return DB::table('vouchers')
            ->where('aktif', true)
            ->where('harga_beli', '>', 0)
            ->whereNotExists(function ($query) use ($userId): void {
                $query->selectRaw('1')
                    ->from('voucher_pelanggan')
                    ->whereColumn('voucher_pelanggan.id_voucher', 'vouchers.id_voucher')
                    ->where('voucher_pelanggan.id_pelanggan', $userId);
            })
            ->orderBy('harga_beli')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function ownedBy(int $userId): Collection
    {
        return DB::table('voucher_pelanggan')
            ->join('vouchers', 'vouchers.id_voucher', '=', 'voucher_pelanggan.id_voucher')
            ->where('voucher_pelanggan.id_pelanggan', $userId)
            ->orderByRaw("CASE WHEN voucher_pelanggan.status = 'tersedia' THEN 0 ELSE 1 END")
            ->orderByDesc('voucher_pelanggan.diklaim_pada')
            ->select('voucher_pelanggan.*', 'vouchers.nama_voucher', 'vouchers.deskripsi', 'vouchers.code', 'vouchers.tipe_diskon', 'vouchers.nilai_diskon', 'vouchers.min_belanja', 'vouchers.min_produk', 'vouchers.tipe_pesanan', 'vouchers.id_produk_gratis')
            ->get();
    }

    /**
     * @param  array<int, array{id_produk: int, harga: int, jumlah: int}>  $items
     * @return array{discount: int, reason: string}|null
     */
    public function quote(object $voucher, array $items, string $orderType): ?array
    {
        if ($voucher->tipe_pesanan !== null && $voucher->tipe_pesanan !== $orderType) {
            return null;
        }

        $total = array_sum(array_map(
            static fn (array $item): int => $item['harga'] * $item['jumlah'],
            $items,
        ));
        $quantity = array_sum(array_column($items, 'jumlah'));

        if ($total < (int) $voucher->min_belanja || $quantity < (int) $voucher->min_produk) {
            return null;
        }

        if ($voucher->tipe_diskon === 'produk_gratis') {
            foreach ($items as $item) {
                if ($item['id_produk'] === (int) $voucher->id_produk_gratis && $item['jumlah'] > 0) {
                    return [
                        'discount' => min($total, $item['harga']),
                        'reason' => 'Gratis satu '.$item['nama_produk'],
                    ];
                }
            }

            return null;
        }

        $discount = match ($voucher->tipe_diskon) {
            'persen' => (int) floor($total * ((int) $voucher->nilai_diskon / 100)),
            'nominal' => (int) $voucher->nilai_diskon,
            default => 0,
        };

        if ($voucher->maks_diskon !== null) {
            $discount = min($discount, (int) $voucher->maks_diskon);
        }

        $discount = min($discount, $total);

        return $discount > 0
            ? ['discount' => $discount, 'reason' => 'Diskon voucher']
            : null;
    }

    /**
     * @param  array<int, array{id_produk: int, harga: int, jumlah: int, nama_produk: string}>  $items
     * @return Collection<int, object>
     */
    public function eligibleForCheckout(int $userId, array $items, string $orderType): Collection
    {
        return DB::table('voucher_pelanggan')
            ->join('vouchers', 'vouchers.id_voucher', '=', 'voucher_pelanggan.id_voucher')
            ->where('voucher_pelanggan.id_pelanggan', $userId)
            ->where('voucher_pelanggan.status', 'tersedia')
            ->where('vouchers.aktif', true)
            ->select('voucher_pelanggan.id_voucher_pelanggan', 'vouchers.*')
            ->get()
            ->map(function (object $voucher) use ($items, $orderType): ?object {
                $quote = $this->quote($voucher, $items, $orderType);
                if ($quote === null) {
                    return null;
                }

                $voucher->potongan = $quote['discount'];

                return $voucher;
            })
            ->filter()
            ->values();
    }

    public function claimTask(int $userId, string $taskId): string
    {
        $task = collect($this->tasksFor($userId))->firstWhere('id', $taskId);
        if ($task === null || $task['progress'] < $task['goal'] || $task['claimed'] || $task['reward'] === null) {
            throw ValidationException::withMessages(['task' => 'Tugas belum selesai atau hadiahnya sudah diklaim.']);
        }

        $this->issue($userId, (int) $task['reward']->id_voucher, 'tugas');

        return $task['reward']->nama_voucher;
    }

    public function redeemCode(int $userId, string $code): string
    {
        return DB::transaction(function () use ($userId, $code): string {
            $voucher = DB::table('vouchers')
                ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
                ->where('aktif', true)
                ->lockForUpdate()
                ->first();

            if ($voucher === null || (int) $voucher->harga_beli > 0) {
                throw ValidationException::withMessages(['code' => 'Kode voucher tidak ditemukan atau tidak dapat ditukar.']);
            }

            if ($voucher->batas_klaim !== null && DB::table('voucher_pelanggan')
                ->where('id_voucher', $voucher->id_voucher)
                ->where('sumber', 'kode')
                ->count() >= (int) $voucher->batas_klaim) {
                throw ValidationException::withMessages(['code' => 'Kuota kode promo sudah habis.']);
            }

            $this->issue($userId, (int) $voucher->id_voucher, 'kode');

            return $voucher->nama_voucher;
        });
    }

    public function issue(int $userId, int $voucherId, string $source): void
    {
        if (DB::table('voucher_pelanggan')
            ->where('id_pelanggan', $userId)
            ->where('id_voucher', $voucherId)
            ->exists()) {
            throw ValidationException::withMessages(['voucher' => 'Voucher ini sudah pernah diklaim atau dibeli.']);
        }

        DB::table('voucher_pelanggan')->insert([
            'id_pelanggan' => $userId,
            'id_voucher' => $voucherId,
            'sumber' => $source,
            'status' => 'tersedia',
            'diklaim_pada' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function referralCodeFor(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = Str::upper(Str::random(10));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        $user->forceFill(['referral_code' => $code])->save();

        return $code;
    }
}
