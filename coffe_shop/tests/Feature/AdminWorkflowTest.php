<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_links_to_all_management_workflows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard.index', ['page' => 'admin']))
            ->assertOk()
            ->assertSee('CRUD Produk')
            ->assertSee('CRUD Pengguna')
            ->assertSee('Kelola Transaksi')
            ->assertSee('Kelola Stok')
            ->assertSee(route('admin.users.index'))
            ->assertSee(route('admin.stock.index'));

        $this->get(route('cashier.products.index'))->assertOk();
        $this->get(route('cashier.transactions.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.stock.index'))->assertOk();

        $this->post(route('cashier.products.store'), [
            'nama_produk' => 'Produk Admin Uji',
            'kategori' => 'Kopi',
            'harga' => 15000,
            'stok' => 4,
        ])->assertRedirect(route('cashier.products.index'));
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Produk Admin Uji', 'stok' => 4]);
    }

    public function test_admin_can_create_update_and_deactivate_users_without_erasing_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.users.store'), [
            'name' => 'Pelanggan Uji',
            'username' => 'pelanggan-uji',
            'email' => 'pelanggan-uji@example.com',
            'role' => 'pelanggan',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])->assertRedirect(route('admin.users.index'));

        $userId = (int) DB::table('users')->where('username', 'pelanggan-uji')->value('id');
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'role' => 'pelanggan',
            'akun_aktif' => true,
        ]);
        $this->delete(route('admin.users.destroy', $admin->id))->assertForbidden();
        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $userId,
            'tanggal_transaksi' => now(),
            'total_harga' => 18000,
            'status_pesanan' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');

        $this->put(route('admin.users.update', $userId), [
            'name' => 'Pelanggan Diperbarui',
            'username' => 'pelanggan-baru',
            'email' => 'pelanggan-baru@example.com',
            'role' => 'kasir',
            'akun_aktif' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => 'Pelanggan Diperbarui',
            'username' => 'pelanggan-baru',
            'email' => 'pelanggan-baru@example.com',
            'role' => 'kasir',
            'akun_aktif' => true,
        ]);

        $this->delete(route('admin.users.destroy', $userId))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $userId, 'akun_aktif' => false]);
        $this->assertDatabaseHas('transaksi', ['id_transaksi' => $transactionId, 'id_pelanggan' => $userId]);
    }

    public function test_admin_can_view_and_unlock_a_blocked_customer_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'username' => 'blocked-customer',
            'role' => 'pelanggan',
            'login_failed_attempts' => 3,
            'login_locked_until' => now()->addMinutes(10),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'terkunci']))
            ->assertOk()
            ->assertSee('blocked-customer')
            ->assertSee('Terkunci sampai');

        $this->post(route('admin.users.unlock', $customer->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'login_failed_attempts' => 0,
            'login_locked_until' => null,
        ]);
    }

    public function test_admin_can_adjust_stock_and_download_a_stock_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $productId = (int) DB::table('produk')->where('nama_produk', 'Kopi Susu Kala')->value('id_produk');

        $this->put(route('admin.stock.update', $productId), ['stok' => 18])
            ->assertRedirect(route('admin.stock.index'));

        $this->assertDatabaseHas('produk', ['id_produk' => $productId, 'stok' => 18]);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => $productId,
            'nama_bahan' => 'Kopi Susu Kala',
            'tipe_pergerakan' => 'penyesuaian_masuk',
            'jumlah' => 6,
            'id_admin' => $admin->id,
        ]);

        $this->get(route('admin.stock.report'))->assertDownload();

        $this->put(route('admin.stock.update', $productId), ['stok' => -1])
            ->assertSessionHasErrors('stok');
    }

    public function test_admin_can_manage_ingredient_stock_and_preserve_its_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSee('Biji Kopi')
            ->assertSee('5 kg')
            ->assertSee('Creamer Latte')
            ->assertSee('1 kg');

        $this->post(route('admin.stock.ingredients.store'), [
            'nama_bahan' => 'Sirup Vanilla',
            'ukuran' => '750 ml',
            'stok' => 4,
        ])->assertRedirect(route('admin.stock.index'));

        $ingredientId = (int) DB::table('bahan_baku')->where('nama_bahan', 'Sirup Vanilla')->value('id_bahan');
        $this->assertDatabaseHas('riwayat_stok', [
            'id_bahan' => $ingredientId,
            'nama_bahan' => 'Sirup Vanilla',
            'tipe_pergerakan' => 'stok_awal',
            'jumlah' => 4,
            'satuan' => 'kemasan',
            'id_admin' => $admin->id,
        ]);

        $this->put(route('admin.stock.ingredients.update', $ingredientId), [
            'nama_bahan' => 'Sirup Vanilla',
            'ukuran' => '750 ml',
            'stok' => 7,
        ])->assertRedirect(route('admin.stock.index'));

        $this->assertDatabaseHas('bahan_baku', ['id_bahan' => $ingredientId, 'stok' => 7]);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_bahan' => $ingredientId,
            'tipe_pergerakan' => 'penyesuaian_masuk',
            'jumlah' => 3,
        ]);

        $this->delete(route('admin.stock.ingredients.destroy', $ingredientId))
            ->assertRedirect(route('admin.stock.index'));

        $this->assertDatabaseMissing('bahan_baku', ['id_bahan' => $ingredientId]);
        $this->assertDatabaseHas('riwayat_stok', [
            'nama_bahan' => 'Sirup Vanilla',
            'tipe_pergerakan' => 'penyesuaian_keluar',
            'jumlah' => 7,
            'id_bahan' => null,
        ]);
    }

    public function test_products_with_active_orders_cannot_be_deleted_before_the_order_finishes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create();
        $productId = DB::table('produk')->insertGetId([
            'nama_produk' => 'Produk Pesanan Aktif',
            'harga' => 12000,
            'stok' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_produk');
        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $customer->id,
            'tanggal_transaksi' => now(),
            'total_harga' => 12000,
            'status_pesanan' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');
        DB::table('detail_transaksi')->insert([
            'id_transaksi' => $transactionId,
            'id_produk' => $productId,
            'nama_produk' => 'Produk Pesanan Aktif',
            'harga_satuan' => 12000,
            'jumlah' => 1,
            'subtotal_harga' => 12000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('cashier.products.destroy', $productId))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('produk', ['id_produk' => $productId]);
    }

    public function test_cashier_and_customer_cannot_manage_admin_accounts_or_stock(): void
    {
        foreach (['kasir', 'pelanggan'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)
                ->get(route('admin.users.index'))
                ->assertForbidden();
            $this->get(route('admin.stock.index'))->assertForbidden();
        }
    }
}
