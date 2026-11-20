<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 16)->nullable()->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('vouchers', function (Blueprint $table): void {
            $table->id('id_voucher');
            $table->string('code', 40)->unique();
            $table->string('nama_voucher', 150);
            $table->text('deskripsi');
            $table->string('tipe_diskon', 24);
            $table->unsignedInteger('nilai_diskon')->default(0);
            $table->unsignedBigInteger('harga_beli')->default(0);
            $table->unsignedBigInteger('min_belanja')->default(0);
            $table->unsignedInteger('min_produk')->default(0);
            $table->string('tipe_pesanan', 16)->nullable();
            $table->foreignId('id_produk_gratis')->nullable()->constrained('produk', 'id_produk')->nullOnDelete();
            $table->unsignedBigInteger('maks_diskon')->nullable();
            $table->unsignedInteger('batas_klaim')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('voucher_pelanggan', function (Blueprint $table): void {
            $table->id('id_voucher_pelanggan');
            $table->foreignId('id_pelanggan')->constrained('users')->cascadeOnDelete();
            $table->foreignId('id_voucher')->constrained('vouchers', 'id_voucher')->cascadeOnDelete();
            $table->foreignId('id_transaksi')->nullable()->constrained('transaksi', 'id_transaksi')->nullOnDelete();
            $table->string('sumber', 16);
            $table->string('status', 16)->default('tersedia');
            $table->timestamp('diklaim_pada');
            $table->timestamp('digunakan_pada')->nullable();
            $table->timestamps();
            $table->unique(['id_pelanggan', 'id_voucher']);
        });

        $freeLatteId = DB::table('produk')
            ->where('nama_produk', 'Signature Iced Latte')
            ->value('id_produk');

        if ($freeLatteId === null) {
            throw new RuntimeException('Produk Signature Iced Latte diperlukan untuk menyiapkan voucher gratis.');
        }

        $vouchers = [
            [
                'code' => 'KALA30',
                'nama_voucher' => 'Diskon Sambutan 30%',
                'deskripsi' => 'Diskon 30% untuk pesanan dine-in minimal 3 produk. Berlaku untuk 15 pelanggan pertama.',
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 30,
                'min_belanja' => 0,
                'min_produk' => 3,
                'tipe_pesanan' => 'dine_in',
                'batas_klaim' => 15,
            ],
            [
                'code' => 'TOPUP20',
                'nama_voucher' => 'Cashback 20%',
                'deskripsi' => 'Potongan hingga Rp20.000 untuk pesanan minimal Rp50.000.',
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 20,
                'min_belanja' => 50000,
                'maks_diskon' => 20000,
            ],
            [
                'code' => 'LATTE-FREE',
                'nama_voucher' => 'Gratis 1 Signature Iced Latte',
                'deskripsi' => 'Gratis satu Signature Iced Latte saat produk tersebut ada di keranjang.',
                'tipe_diskon' => 'produk_gratis',
                'id_produk_gratis' => $freeLatteId,
            ],
            [
                'code' => 'REFERRAL35',
                'nama_voucher' => 'Potongan Langsung Rp35.000',
                'deskripsi' => 'Potongan Rp35.000 untuk pesanan minimal Rp50.000.',
                'tipe_diskon' => 'nominal',
                'nilai_diskon' => 35000,
                'min_belanja' => 50000,
            ],
            [
                'code' => 'BUNDLE-5-CANGKIR',
                'nama_voucher' => 'Bundle Ngopi 5 Cangkir',
                'deskripsi' => 'Diskon 40% untuk pesanan dengan minimal total Rp99.000.',
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 40,
                'harga_beli' => 99000,
                'min_belanja' => 99000,
            ],
            [
                'code' => 'PASTRY-COLD-BREW',
                'nama_voucher' => 'Pastry & Cold Brew',
                'deskripsi' => 'Potongan Rp25.000 untuk pesanan minimal Rp50.000.',
                'tipe_diskon' => 'nominal',
                'nilai_diskon' => 25000,
                'harga_beli' => 10000,
                'min_belanja' => 50000,
            ],
            [
                'code' => 'WEEKEND-PASS',
                'nama_voucher' => 'Weekend Pour-Over Pass',
                'deskripsi' => 'Diskon 35% untuk pesanan dine-in.',
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 35,
                'harga_beli' => 15000,
                'tipe_pesanan' => 'dine_in',
            ],
        ];

        foreach ($vouchers as $voucher) {
            DB::table('vouchers')->updateOrInsert(
                ['code' => $voucher['code']],
                [
                    ...$voucher,
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_pelanggan');
        Schema::dropIfExists('vouchers');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
