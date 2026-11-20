<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'captcha' => '1',
        ])->assertSessionHasErrors('email');

        $this->post(route('admin.login.store'), [
            'username' => 'cashier-user',
            'password' => 'secret-pass',
        ])->assertRedirect(route('dashboard.index', ['page' => 'kasir']));

        $this->assertAuthenticated();
    }

    public function test_existing_cashier_can_login_with_its_name_and_legacy_plaintext_password_is_rehashed(): void
    {
        $cashier = User::factory()->create([
            'name' => 'Nadia afrianti',
            'username' => 'Nadia',
            'role' => 'kasir',
        ]);
        DB::table('users')->where('id', $cashier->id)->update(['password' => 'Kasir123']);

        $this->post(route('admin.login.store'), [
            'username' => 'Nadia afrianti',
            'password' => 'Kasir123',
        ])->assertRedirect(route('dashboard.index', ['page' => 'kasir']));

        $this->assertAuthenticatedAs($cashier);
        $this->assertTrue(Hash::check('Kasir123', $cashier->fresh()->password));
        $this->assertNotNull($cashier->fresh()->last_login_at);
    }

    public function test_admin_is_sent_to_its_own_dashboard_and_cannot_open_cashier_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard.index', ['page' => 'admin']))
            ->assertOk()
            ->assertSee('Portal admin');

        $this->get(route('staff.profile'))
            ->assertOk()
            ->assertSee('Profil Admin');
        $this->get(route('dashboard.index', ['page' => 'kasir']))->assertForbidden();
    }

    public function test_staff_uses_admin_profile_and_cannot_access_customer_pages_or_cart_actions(): void
    {
        $cashier = User::factory()->create([
            'name' => 'Nadia Kasir',
            'username' => 'nadia-kasir',
            'role' => 'kasir',
        ]);

        $this->actingAs($cashier)
            ->get(route('profile.show'))
            ->assertRedirect(route('staff.profile'));

        $this->get(route('staff.profile'))
            ->assertOk()
            ->assertSee('Nadia Kasir')
            ->assertSee('nadia-kasir')
            ->assertSeeInOrder([route('staff.profile'), 'Logout', 'COFFEE_SHOP']);

        $this->get(route('catalog.index'))
            ->assertRedirect(route('dashboard.index', ['page' => 'kasir']));
        $this->get(route('cart.index'))
            ->assertRedirect(route('dashboard.index', ['page' => 'kasir']));
        $this->post(route('cart.store'), ['menu_id' => 1])->assertForbidden();
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
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => $productId,
            'tipe_pergerakan' => 'stok_awal',
            'jumlah' => 12,
        ]);
        $this->put(route('cashier.products.update', $productId), [
            'nama_produk' => 'Kopi Uji Susu',
            'kategori' => 'Kopi',
            'deskripsi' => 'Menu yang diperbarui',
            'harga' => 22000,
            'stok' => 8,
            'gambar' => '',
        ])->assertRedirect();
        $this->assertDatabaseHas('produk', ['id_produk' => $productId, 'nama_produk' => 'Kopi Uji Susu']);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => $productId,
            'tipe_pergerakan' => 'penyesuaian_keluar',
            'jumlah' => 4,
        ]);

        $this->delete(route('cashier.products.destroy', $productId))->assertRedirect();
        $this->assertDatabaseMissing('produk', ['id_produk' => $productId]);
    }

    public function test_cashier_has_separate_product_and_transaction_pages_with_table_status_control(): void
    {
        $customer = User::factory()->create();
        $cashier = User::factory()->create(['role' => 'kasir']);
        $tableId = (int) DB::table('meja')->where('nomor_meja', 'Meja 1')->value('id_meja');
        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $customer->id,
            'id_meja' => $tableId,
            'tanggal_transaksi' => now(),
            'waktu_tiba' => now()->addHour(),
            'total_harga' => 18000,
            'metode_pembayaran' => 'saldo_coffee_shop',
            'status_pembayaran' => 'berhasil',
            'status_pesanan' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');
        DB::table('meja')->where('id_meja', $tableId)->update(['status_meja' => 'dipesan']);

        $this->actingAs($cashier)
            ->get(route('cashier.products.index'))
            ->assertOk()
            ->assertSee('Edit barang')
            ->assertSee('Hapus barang?')
            ->assertSee('products_page=2');

        $this->get(route('cashier.transactions.index'))
            ->assertOk()
            ->assertSee('Transaksi dine-in')
            ->assertSee('Transaksi take-away')
            ->assertSee($customer->name)
            ->assertSee('Saldo Coffee Shop');

        $this->put(route('cashier.tables.status', $tableId), ['status_meja' => 'digunakan'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'digunakan']);

        $this->from(route('cashier.transactions.index'))
            ->put(route('cashier.tables.status', $tableId), ['status_meja' => 'tersedia'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'tersedia']);

        $this->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'completed'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'tersedia']);
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

    public function test_completing_or_cancelling_a_dine_in_order_never_releases_its_table_automatically(): void
    {
        $customer = User::factory()->create();
        $cashier = User::factory()->create(['role' => 'kasir']);
        $tableId = (int) DB::table('meja')->value('id_meja');
        $transactionId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $customer->id,
            'id_meja' => $tableId,
            'tanggal_transaksi' => now(),
            'total_harga' => 10000,
            'metode_pembayaran' => 'saldo_coffee_shop',
            'status_pembayaran' => 'berhasil',
            'status_pesanan' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_transaksi');
        DB::table('meja')->where('id_meja', $tableId)->update(['status_meja' => 'digunakan']);

        $this->actingAs($cashier)
            ->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'completed'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'digunakan']);

        $this->put(route('cashier.tables.status', $tableId), ['status_meja' => 'tersedia'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'tersedia']);
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

    public function test_cashier_dashboard_splits_wallet_and_other_payment_revenue(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $customer = User::factory()->create();

        foreach ([
            ['total_harga' => 18000, 'metode_pembayaran' => 'saldo_coffee_shop'],
            ['total_harga' => 24000, 'metode_pembayaran' => 'qris'],
        ] as $sale) {
            DB::table('transaksi')->insert([
                'id_pelanggan' => $customer->id,
                'tanggal_transaksi' => now(),
                'total_harga' => $sale['total_harga'],
                'metode_pembayaran' => $sale['metode_pembayaran'],
                'status_pembayaran' => 'berhasil',
                'status_pesanan' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($cashier)
            ->get(route('dashboard.index', ['page' => 'kasir', 'periode' => 'hari']))
            ->assertOk()
            ->assertSee('Omzet Saldo Coffee Shop')
            ->assertSee('Omzet pembayaran lain')
            ->assertSee('Rp 18.000')
            ->assertSee('Rp 24.000');
    }

    public function test_transaction_lists_have_independent_pagination(): void
    {
        $customer = User::factory()->create();
        $cashier = User::factory()->create(['role' => 'kasir']);
        $tableId = (int) DB::table('meja')->value('id_meja');

        foreach (range(1, 11) as $orderNumber) {
            foreach ([$tableId, null] as $table) {
                DB::table('transaksi')->insert([
                    'id_pelanggan' => $customer->id,
                    'id_meja' => $table,
                    'tanggal_transaksi' => now()->subMinutes($orderNumber),
                    'total_harga' => 10000,
                    'status_pesanan' => 'paid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->actingAs($cashier)
            ->get(route('cashier.transactions.index'))
            ->assertOk()
            ->assertSee('dine_page=2')
            ->assertSee('takeaway_page=2')
            ->assertSee('11 pesanan');
    }
}
