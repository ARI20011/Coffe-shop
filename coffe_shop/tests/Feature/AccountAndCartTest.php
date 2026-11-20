<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountAndCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_guest_profile_and_is_sent_to_login_when_adding_menu(): void
    {
        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Mode guest')
            ->assertSee('Guest');

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('login dulu')
            ->assertSee(route('login', ['next' => 'home']));

        $response = $this->post(route('cart.store'), ['menu_id' => 1]);
        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('cart');

        $this->assertGuest();
    }

    public function test_customer_can_login_add_menu_and_open_persistent_cart(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'customer@example.com',
            'password' => 'password',
            'captcha' => '1',
            'next' => 'home',
        ])->assertRedirect(route('dashboard.index', ['page' => 'home']));

        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'pelanggan']);

        $this->post(route('cart.store'), ['menu_id' => 1])->assertRedirect();
        $this->post(route('cart.store'), ['menu_id' => 1])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Kopi Susu Kala')
            ->assertSee('Rp 36.000')
            ->assertSee('aria-label="Keranjang, 2 item"', false);
    }

    public function test_cart_rejects_sold_out_items_and_limits_quantity_to_stock(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('catalog.index'))
            ->post(route('cart.store'), ['menu_id' => 3])
            ->assertSessionHasErrors('cart');

        foreach (range(1, 5) as $quantity) {
            $this->post(route('cart.store'), ['menu_id' => 2])->assertRedirect();
        }

        $this->from(route('cart.index'))
            ->post(route('cart.store'), ['menu_id' => 2])
            ->assertSessionHasErrors('cart');

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Rp 75.000');
    }

    public function test_customer_can_register_and_return_to_home(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Kopi Customer',
            'username' => 'kopi-customer',
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
            'captcha' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'username' => 'kopi-customer',
            'role' => 'pelanggan',
            'saldo' => 50000,
        ]);

        $this->post(route('login.store'), [
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
            'captcha' => '1',
        ])->assertRedirect(route('dashboard.index', ['page' => 'home']));

        $this->assertAuthenticated();
    }

    public function test_registration_requires_the_robot_checkbox(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Kopi Customer',
            'username' => 'kopi-customer',
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
        ])->assertSessionHasErrors('captcha');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        $this->assertGuest();
    }

    public function test_customer_can_checkout_and_view_order_history_on_profile(): void
    {
        $user = User::factory()->create(['saldo' => 100000]);
        $pickupAt = now()->addHour()->startOfMinute();
        $this->actingAs($user)->withSession(['cart' => [1 => 2, 2 => 1]]);

        $this->post(route('cart.checkout'), [
            'order_type' => 'takeaway',
            'pickup_at' => $pickupAt->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
        ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionMissing('cart');

        $this->assertDatabaseHas('transaksi', [
            'id_pelanggan' => $user->id,
            'total_harga' => 51000,
            'status_pesanan' => 'paid',
            'status_pembayaran' => 'berhasil',
            'waktu_pengambilan' => $pickupAt->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'saldo' => 49000]);
        $this->assertDatabaseHas('detail_transaksi', [
            'nama_produk' => 'Kopi Susu Kala',
            'jumlah' => 2,
            'subtotal_harga' => 36000,
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => 1,
            'tipe_pergerakan' => 'pembelian',
            'jumlah' => 2,
        ]);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Riwayat checkout')
            ->assertSee('Kopi Susu Kala')
            ->assertSee('Rp 51.000')
            ->assertSee('Pelanggan');
    }

    public function test_profile_history_hides_orders_older_than_thirty_days_without_deleting_them(): void
    {
        $user = User::factory()->create();
        $oldOrderId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $user->id,
            'tanggal_transaksi' => now()->subDays(31),
            'total_harga' => 10000,
            'status_pesanan' => 'completed',
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ], 'id_transaksi');
        $recentOrderId = DB::table('transaksi')->insertGetId([
            'id_pelanggan' => $user->id,
            'tanggal_transaksi' => now()->subDays(29),
            'total_harga' => 12000,
            'status_pesanan' => 'completed',
            'created_at' => now()->subDays(29),
            'updated_at' => now()->subDays(29),
        ], 'id_transaksi');

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('selama 30 hari')
            ->assertSee('#'.$recentOrderId)
            ->assertDontSee('#'.$oldOrderId);

        $this->assertDatabaseHas('transaksi', ['id_transaksi' => $oldOrderId]);
    }

    public function test_customer_can_place_a_dine_in_order_and_reserved_table_is_unavailable(): void
    {
        $customer = User::factory()->create(['saldo' => 50000]);
        $arrivalAt = now()->addHour()->startOfMinute();
        $tableId = (int) DB::table('meja')->where('nomor_meja', 'Meja 1')->value('id_meja');
        $this->actingAs($customer)->withSession([
            'order_type' => 'dine_in',
            'dine_in_table' => $tableId,
        ]);

        $this->post(route('cart.store'), ['menu_id' => 1])
            ->assertRedirect()
            ->assertSessionHas('dine_in_cart', [1 => 1])
            ->assertSessionMissing('cart');

        $this->post(route('cart.checkout'), [
            'order_type' => 'dine_in',
            'table_id' => $tableId,
            'arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
        ])->assertRedirect(route('profile.show'));

        $transactionId = (int) DB::table('transaksi')
            ->where('id_pelanggan', $customer->id)
            ->value('id_transaksi');
        $this->assertDatabaseHas('transaksi', [
            'id_transaksi' => $transactionId,
            'id_meja' => $tableId,
            'total_harga' => 18000,
            'waktu_tiba' => $arrivalAt->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('meja', [
            'id_meja' => $tableId,
            'status_meja' => 'dipesan',
        ]);
        $this->assertDatabaseHas('produk', [
            'id_produk' => 1,
            'stok' => 11,
        ]);

        $otherCustomer = User::factory()->create();
        $this->actingAs($otherCustomer)
            ->from(route('dashboard.index', ['page' => 'menu']))
            ->post(route('cart.mode'), [
                'order_type' => 'dine_in',
                'table_id' => $tableId,
            ])
            ->assertSessionHasErrors('table_id');

        $this->withSession(['order_type' => 'dine_in'])
            ->get(route('dashboard.index', ['page' => 'menu']))
            ->assertOk()
            ->assertDontSee('Meja 1');

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->put(route('cashier.transactions.status', $transactionId), ['status_pesanan' => 'cancelled'])
            ->assertRedirect();

        $this->assertDatabaseHas('meja', [
            'id_meja' => $tableId,
            'status_meja' => 'dipesan',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->put(route('cashier.tables.status', $tableId), ['status_meja' => 'tersedia'])
            ->assertRedirect();
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'tersedia']);
        $this->assertDatabaseHas('produk', [
            'id_produk' => 1,
            'stok' => 12,
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => 1,
            'tipe_pergerakan' => 'pembelian',
            'jumlah' => 1,
        ]);
        $this->assertDatabaseHas('riwayat_stok', [
            'id_produk' => 1,
            'tipe_pergerakan' => 'pembatalan',
            'jumlah' => 1,
        ]);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 50000]);
    }

    public function test_profile_handles_missing_created_at_for_existing_accounts(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['created_at' => null]);

        $this->actingAs($user->fresh())
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Tanggal tidak tersedia')
            ->assertSee('Saldo Coffee Shop');
    }

    public function test_customer_can_top_up_using_a_simulated_provider(): void
    {
        $user = User::factory()->create(['saldo' => 50000]);

        $this->actingAs($user)
            ->get(route('topup.index'))
            ->assertOk()
            ->assertSee('Saldo saat ini')
            ->assertSee('DANA')
            ->assertSee('QRIS')
            ->assertSee('GoPay');

        $this->post(route('topup.store'), [
            'jumlah' => 25000,
            'metode_pembayaran' => 'qris',
        ])->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'saldo' => 75000]);
        $this->assertDatabaseHas('top_up', [
            'id_pelanggan' => $user->id,
            'jumlah' => 25000,
            'metode_pembayaran' => 'qris',
            'status_topup' => 'berhasil',
        ]);
    }

    public function test_checkout_page_shows_pickup_schedule_and_all_payment_methods(): void
    {
        $user = User::factory()->create(['saldo' => 50000]);

        $this->actingAs($user)
            ->withSession(['cart' => [1 => 1]])
            ->get(route('checkout.show', ['order_type' => 'takeaway']))
            ->assertOk()
            ->assertSee('Waktu pengambilan pesanan')
            ->assertSee('DANA')
            ->assertSee('QRIS')
            ->assertSee('GoPay')
            ->assertSee('checkout-method--disabled')
            ->assertSee('hanya tersedia untuk top up saldo')
            ->assertSee('Saldo Coffee Shop')
            ->assertSee('Rp 18.000');
    }

    public function test_insufficient_wallet_payment_creates_a_failed_order_without_charging_stock(): void
    {
        $user = User::factory()->create(['saldo' => 1000]);
        $this->actingAs($user)->withSession(['cart' => [1 => 1]]);

        $this->post(route('cart.checkout'), [
            'order_type' => 'takeaway',
            'pickup_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
        ])->assertRedirect()
            ->assertSessionHasErrors('payment')
            ->assertSessionHas('cart', [1 => 1]);

        $this->assertDatabaseHas('transaksi', [
            'id_pelanggan' => $user->id,
            'status_pesanan' => 'cancelled',
            'status_pembayaran' => 'gagal',
            'total_harga' => 18000,
        ]);
        $this->assertDatabaseHas('produk', ['id_produk' => 1, 'stok' => 12]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'saldo' => 1000]);
    }

    public function test_checkout_rejects_external_payment_methods_and_leaves_wallet_and_stock_unchanged(): void
    {
        $user = User::factory()->create(['saldo' => 0]);
        $this->actingAs($user)->withSession(['cart' => [1 => 1]]);

        $this->post(route('checkout.store'), [
            'order_type' => 'takeaway',
            'pickup_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'gopay',
        ])->assertSessionHasErrors('metode_pembayaran');

        $this->assertDatabaseMissing('transaksi', ['id_pelanggan' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'saldo' => 0]);
        $this->assertDatabaseHas('produk', ['id_produk' => 1, 'stok' => 12]);
    }

    public function test_menu_displays_database_products_and_available_tables(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->withSession([
                'order_type' => 'dine_in',
                'dine_in_table' => 1,
                'dine_in_cart' => [1 => 1],
            ])
            ->get(route('dashboard.index', ['page' => 'menu']))
            ->assertOk()
            ->assertSee('Kopi Susu Kala')
            ->assertSee('Meja 1')
            ->assertSee('Dine-in')
            ->assertSee('Waktu tiba di lokasi');

        $this->withSession([
            'order_type' => 'takeaway',
            'cart' => [1 => 1],
        ])
            ->get(route('dashboard.index', ['page' => 'menu']))
            ->assertOk()
            ->assertSee('Waktu pengambilan');
    }

    public function test_failed_login_does_not_authenticate_customer(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'customer@example.com',
                'password' => 'wrong-password',
                'captcha' => '1',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_locked_for_fifteen_minutes_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        foreach (range(1, 3) as $attempt) {
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => '1',
            ]);
            $response->assertSessionHasErrors('email');
        }

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'correct-password',
                'captcha' => '1',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->travel(16)->minutes();
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
            'captcha' => '1',
        ])->assertRedirect(route('dashboard.index', ['page' => 'home']));

        $this->assertAuthenticatedAs($user);
    }

    public function test_three_failed_login_captcha_checks_lock_the_customer_account(): void
    {
        $user = User::factory()->create(['email' => 'captcha@example.com']);

        foreach (range(1, 3) as $attempt) {
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);
            $response->assertSessionHasErrors($attempt === 3 ? 'email' : 'captcha');
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'login_failed_attempts' => 3,
        ]);
        $this->assertNotNull($user->fresh()->login_locked_until);
    }

    public function test_forgot_password_sends_a_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_changes_the_password_without_logging_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_logout_clears_the_customer_session_and_cart(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['cart' => [1 => 2]])
            ->post(route('logout'));
        $response->assertRedirect(route('catalog.index'));
        $response->assertSessionMissing('cart');

        $this->assertGuest();
    }
}
