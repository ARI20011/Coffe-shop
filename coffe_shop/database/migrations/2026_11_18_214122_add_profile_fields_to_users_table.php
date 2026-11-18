<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUsername = Schema::hasColumn('users', 'username');
        $hasRole = Schema::hasColumn('users', 'role');
        $hasLastLogin = Schema::hasColumn('users', 'last_login_at');

        Schema::table('users', function (Blueprint $table) use ($hasUsername, $hasRole, $hasLastLogin): void {
            if (! $hasUsername) {
                $table->string('username', 100)->nullable();
            }

            if (! $hasRole) {
                $table->enum('role', ['pelanggan', 'kasir', 'admin'])->default('pelanggan');
            }

            if (! $hasLastLogin) {
                $table->timestamp('last_login_at')->nullable();
            }
        });

        if ($hasRole) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('pelanggan', 'pelangan', 'kasir', 'admin') NOT NULL DEFAULT 'pelanggan'");
            }

            DB::table('users')->where('role', 'pelangan')->update(['role' => 'pelanggan']);

            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('pelanggan', 'kasir', 'admin') NOT NULL DEFAULT 'pelanggan'");
            }
        }

        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'pelanggan']);

        DB::table('users')->select('id', 'name', 'username')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                if (trim((string) $user->username) !== '') {
                    continue;
                }

                $base = trim((string) $user->name);
                $base = $base !== '' ? mb_substr($base, 0, 100) : 'user-'.$user->id;
                $username = $base;
                $suffixNumber = 0;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $suffixNumber++;
                    $suffix = '-'.$user->id.($suffixNumber > 1 ? '-'.$suffixNumber : '');
                    $username = mb_substr($base, 0, 100 - mb_strlen($suffix)).$suffix;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });

        $hasUniqueUsername = collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['username']
        );

        if (! $hasUniqueUsername) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username');
            });
        }
    }

    public function down(): void
    {
        $hasUniqueUsername = collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['username']
        );

        Schema::table('users', function (Blueprint $table) use ($hasUniqueUsername): void {
            if ($hasUniqueUsername) {
                $table->dropUnique(['username']);
            }

            $columns = array_values(array_filter(
                ['username', 'last_login_at'],
                fn (string $column): bool => Schema::hasColumn('users', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
