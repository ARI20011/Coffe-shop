<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (range(1, 8) as $number) {
            DB::table('meja')->insertOrIgnore([
                'nomor_meja' => 'Meja '.$number,
                'kapasitas' => 4,
                'status_meja' => 'tersedia',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
