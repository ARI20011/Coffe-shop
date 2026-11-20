<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('akun_aktif')->default(true);
            $table->unsignedTinyInteger('login_failed_attempts')->default(0);
            $table->timestamp('login_locked_until')->nullable();
        });

        Schema::table('riwayat_stok', function (Blueprint $table): void {
            $table->foreignId('id_produk')->nullable()->constrained('produk', 'id_produk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_stok', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_produk');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['akun_aktif', 'login_failed_attempts', 'login_locked_until']);
        });
    }
};
