<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transaksi')
            ->where('metode_pembayaran', 'saldo_coffee_shop')
            ->update(['metode_pembayaran' => 'data_lama']);
    }

    public function down(): void {}
};
