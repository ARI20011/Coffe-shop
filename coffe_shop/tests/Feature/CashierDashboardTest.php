<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashierDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_accepts_only_staff_and_redirects_cashier_to_its_dashboard(): void
    {
        $customer = User::factory()->create([
            'username' => 'customer-user',
            'role' => 'pelanggan',
            'password' => 'secret-pass',
        ]);
        $cashier = User::factory()->create([
            'username' => 'cashier-user',
            'role' => 'kasir',
            'password' => 'secret-pass',
        ]);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Username')
            ->assertSee('Kata sandi')
            ->assertDontSee('name="email"', false);

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'username' => $customer->username,
                'password' => 'secret-pass',
            ])
            ->assertSessionHasErrors('username');

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'secret-pass',
        ])->assertSessionHasErrors('email');

        $this->post(route('admin.login.store'), [
            'username' => 'cashier-user',
            'password' => 'secret-pass',
        ])->assertRedirect(route('dashboard.index', ['page' => 'kasir']));

        $this->assertAuthenticated();
    }

    public function test_admin_is_sent_to_its_own_dashboard_and_cannot_open_cashier_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard.index', ['page' => 'admin']))
            ->assertOk()
            ->assertSee('Portal admin');

        $this->get(route('dashboard.index', ['page' => 'kasir']))->assertForbidden();
    }

    public function test_cashier_can_create_update_and_delete_products(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $this->actingAs($cashier);

        $this->get(route('dashboard.index', ['page' => 'kasir']))
            ->assertOk()
            ->assertSee('Dashboard kasir')
            ->assertSee('Kelola produk')
            ->assertSee('Statistik penjualan');

        $this->post(route('cashier.products.store'), [
            'nama_produk' => 'Kopi Uji',
            'kategori' => 'Kopi',
            'deskripsi' => 'Menu uji',
            'harga' => 18000,
            'stok' => 12,
            'gambar' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Kopi Uji',
            'harga' => 18000,
            'stok' => 12,
        ]);
        $this->assertDatabaseHas('kategori', ['nama_kategori' => 'Kopi']);

        $productId = DB::table('produk')->where('nama_produk', 'Kopi Uji')->value('id_produk');
        $this->put(route('cashier.products.update', $productId), [
            'nama_produk' => 'Kopi Uji Susu',
            'kategori' => 'Kopi',
            'deskripsi' => 'Menu yang diperbarui',
            'harga' => 22000,
            'stok' => 8,
            'gambar' => '',
        ])->assertRedirect();
        $this->assertDatabaseHas('produk', ['id_produk' => $productId, 'nama_produk' => 'Kopi Uji Susu']);

        $this->delete(route('cashier.products.destroy', $productId))->assertRedirect();
        $this->assertDatabaseMissing('produk', ['id_produk' => $productId]);
    }

    public function test_customers_cannot_open_cashier_dashboard_or_manage_products(): void
    {
        $customer = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($customer)
            ->get(route('dashboard.index', ['page' => 'kasir']))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('cashier.products.store'), [
                'nama_produk' => 'Tidak boleh',
                'harga' => 1000,
                'stok' => 1,
            ])
            ->assertForbidden();
    }

    public function test_cashier_order_status_follows_paid_shipped_completed_sequence_and_exports_report(): void
    {
        $customer = User::factory()->create();
        $cashier = User::factory()->create(['role' => 'kasir']);
        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $customer->id,
            'tanggal_transaksi' => now(),
            'total_harga' => 18000,
            'status_pesanan' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');

        $this->actingAs($cashier)
            ->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'shipped'])
            ->assertSessionHasErrors('status_pesanan');
        $this->assertDatabaseHas('transaksi', ['id_transaksi' => $transactionId, 'status_pesanan' => 'menunggu']);

        $this->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'paid'])->assertRedirect();
        $this->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'shipped'])->assertRedirect();
        $this->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('transaksi', ['id_transaksi' => $transactionId, 'status_pesanan' => 'completed']);

        $this->get(route('cashier.transactions.report', ['periode' => 'hari']))
            ->assertDownload();
    }

    public function test_cashier_statistics_show_popular_and_rarely_bought_products_for_selected_period(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $customer = User::factory()->create();
        $popularId = DB::table('produk')->insertGetId([
            'nama_produk' => 'Espresso',
            'harga' => 12000,
            'stok' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_produk');
        DB::table('produk')->insert([
            'nama_produk' => 'Latte',
            'harga' => 18000,
            'stok' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $customer->id,
            'tanggal_transaksi' => now(),
            'total_harga' => 36000,
            'status_pesanan' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');
        DB::table('detail_transaksi')->insert([
            'id_transaksi' => $transactionId,
            'id_produk' => $popularId,
            'nama_produk' => 'Espresso',
            'harga_satuan' => 12000,
            'jumlah' => 3,
            'subtotal_harga' => 36000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('dashboard.index', ['page' => 'kasir', 'periode' => 'hari']))
            ->assertOk()
            ->assertSee('Hari ini')
            ->assertSee('Espresso')
            ->assertSee('3 item terjual')
            ->assertSee('Latte')
            ->assertSee('Paling jarang dibeli');

        foreach (['minggu' => 'Minggu ini', 'bulan' => 'Bulan ini'] as $period => $label) {
            $this->get(route('dashboard.index', ['page' => 'kasir', 'periode' => $period]))
                ->assertOk()
                ->assertSee($label)
                ->assertSee('Espresso');
        }
    }
}
