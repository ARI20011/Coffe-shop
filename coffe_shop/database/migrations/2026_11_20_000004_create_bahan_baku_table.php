<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_baku', function (Blueprint $table): void {
            $table->id('id_bahan');
            $table->string('nama_bahan', 150);
            $table->string('ukuran', 30);
            $table->unsignedInteger('stok')->default(0);
            $table->timestamps();
            $table->unique(['nama_bahan', 'ukuran']);
        });

        Schema::table('riwayat_stok', function (Blueprint $table): void {
            $table->foreignId('id_bahan')->nullable()->constrained('bahan_baku', 'id_bahan')->nullOnDelete();
        });

        $now = now();
        foreach ([
            ['nama_bahan' => 'Biji Kopi', 'ukuran' => '5 kg', 'stok' => 5],
            ['nama_bahan' => 'Creamer Latte', 'ukuran' => '1 kg', 'stok' => 10],
        ] as $ingredient) {
            $ingredientId = DB::table('bahan_baku')->insertGetId([
                ...$ingredient,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_bahan');

            DB::table('riwayat_stok')->insert([
                'id_bahan' => $ingredientId,
                'nama_bahan' => $ingredient['nama_bahan'],
                'tipe_pergerakan' => 'stok_awal',
                'jumlah' => $ingredient['stok'],
                'satuan' => 'kemasan',
                'tanggal_catat' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('riwayat_stok', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_bahan');
        });

        Schema::dropIfExists('bahan_baku');
    }
};
