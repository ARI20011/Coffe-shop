<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_redeem_a_code_once_and_the_home_promo_disappears(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)
            ->post(route('vouchers.redeem'), ['code' => 'kala30'])
            ->assertRedirect(route('vouchers.mine'))
            ->assertSessionHas('status');

        $voucherId = (int) DB::table('vouchers')->where('code', 'KALA30')->value('id_voucher');
        $this->assertDatabaseHas('voucher_pelanggan', [
            'id_pelanggan' => $customer->id,
            'id_voucher' => $voucherId,
            'sumber' => 'kode',
            'status' => 'tersedia',
        ]);

        $this->post(route('vouchers.redeem'), ['code' => 'KALA30'])
            ->assertSessionHasErrors('voucher');

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('KALA30');
    }

    public function test_promo_code_is_limited_to_the_first_fifteen_customers(): void
    {
        foreach (range(1, 15) as $number) {
            $customer = User::factory()->create();
            $this->actingAs($customer)
                ->post(route('vouchers.redeem'), ['code' => 'KALA30'])
                ->assertRedirect(route('vouchers.mine'));
        }

        $lastCustomer = User::factory()->create();
        $this->actingAs($lastCustomer)
            ->post(route('vouchers.redeem'), ['code' => 'KALA30'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('voucher_pelanggan', 15);
    }

    public function test_checkout_applies_dine_in_discount_and_marks_voucher_used_only_after_payment(): void
    {
        $customer = User::factory()->create(['saldo' => 100000]);
        $this->actingAs($customer)->post(route('vouchers.redeem'), ['code' => 'KALA30']);
        $voucherId = (int) DB::table('voucher_pelanggan')
            ->where('id_pelanggan', $customer->id)
            ->value('id_voucher_pelanggan');
        $tableId = (int) DB::table('meja')->where('status_meja', 'tersedia')->value('id_meja');
        $arrivalAt = now()->addHour()->startOfMinute();
        $this->withSession([
            'order_type' => 'dine_in',
            'dine_in_table' => $tableId,
            'dine_in_cart' => [1 => 1, 2 => 1, 5 => 1],
        ]);

        $this->get(route('checkout.show', [
            'order_type' => 'dine_in',
            'table_id' => $tableId,
            'voucher_id' => $voucherId,
        ]))
            ->assertOk()
            ->assertSee('Diskon Sambutan 30%')
            ->assertSee('Rp 42.700');

        $this->post(route('checkout.store'), [
            'order_type' => 'dine_in',
            'table_id' => $tableId,
            'arrival_at' => $arrivalAt->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
            'voucher_id' => $voucherId,
        ])->assertRedirect(route('profile.show'));

        $transactionId = (int) DB::table('transaksi')
            ->where('id_pelanggan', $customer->id)
            ->value('id_transaksi');
        $this->assertDatabaseHas('transaksi', [
            'id_transaksi' => $transactionId,
            'total_harga' => 42700,
            'status_pesanan' => 'paid',
            'status_pembayaran' => 'berhasil',
        ]);
        $this->assertDatabaseHas('voucher_pelanggan', [
            'id_voucher_pelanggan' => $voucherId,
            'status' => 'digunakan',
            'id_transaksi' => $transactionId,
        ]);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 57300]);
    }

    public function test_free_latte_voucher_makes_the_eligible_item_cost_zero(): void
    {
        $customer = User::factory()->create(['saldo' => 50000]);
        $voucher = DB::table('vouchers')->where('code', 'LATTE-FREE')->first();
        DB::table('voucher_pelanggan')->insert([
            'id_pelanggan' => $customer->id,
            'id_voucher' => $voucher->id_voucher,
            'sumber' => 'tugas',
            'status' => 'tersedia',
            'diklaim_pada' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $voucherId = (int) DB::table('voucher_pelanggan')
            ->where('id_pelanggan', $customer->id)
            ->value('id_voucher_pelanggan');
        $this->actingAs($customer)->withSession(['cart' => [5 => 1]]);

        $this->post(route('checkout.store'), [
            'order_type' => 'takeaway',
            'pickup_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
            'voucher_id' => $voucherId,
        ])->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('transaksi', [
            'id_pelanggan' => $customer->id,
            'total_harga' => 0,
            'status_pesanan' => 'paid',
        ]);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 50000]);
        $this->assertDatabaseHas('voucher_pelanggan', [
            'id_voucher_pelanggan' => $voucherId,
            'status' => 'digunakan',
        ]);
    }

    public function test_failed_payment_keeps_the_selected_voucher_available_for_retry(): void
    {
        $customer = User::factory()->create(['saldo' => 1000]);
        $this->actingAs($customer)->post(route('vouchers.redeem'), ['code' => 'KALA30']);
        $voucherId = (int) DB::table('voucher_pelanggan')
            ->where('id_pelanggan', $customer->id)
            ->value('id_voucher_pelanggan');
        $tableId = (int) DB::table('meja')->where('status_meja', 'tersedia')->value('id_meja');
        $this->withSession([
            'order_type' => 'dine_in',
            'dine_in_table' => $tableId,
            'dine_in_cart' => [1 => 1, 2 => 1, 5 => 1],
        ]);

        $this->post(route('checkout.store'), [
            'order_type' => 'dine_in',
            'table_id' => $tableId,
            'arrival_at' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_pembayaran' => 'saldo_coffee_shop',
            'voucher_id' => $voucherId,
        ])
            ->assertRedirectContains('voucher_id='.$voucherId)
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('voucher_pelanggan', [
            'id_voucher_pelanggan' => $voucherId,
            'status' => 'tersedia',
            'id_transaksi' => null,
        ]);
        $this->assertDatabaseHas('meja', ['id_meja' => $tableId, 'status_meja' => 'tersedia']);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 1000]);
        $this->assertDatabaseHas('produk', ['id_produk' => 1, 'stok' => 12]);
    }

    public function test_customer_can_buy_a_voucher_and_cannot_buy_the_same_one_twice(): void
    {
        $customer = User::factory()->create(['saldo' => 20000]);
        $voucherId = (int) DB::table('vouchers')->where('code', 'PASTRY-COLD-BREW')->value('id_voucher');

        $this->actingAs($customer)
            ->post(route('vouchers.purchase', $voucherId))
            ->assertRedirect(route('vouchers.mine'));

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 10000]);
        $this->assertDatabaseHas('voucher_pelanggan', [
            'id_pelanggan' => $customer->id,
            'id_voucher' => $voucherId,
            'sumber' => 'beli',
        ]);

        $this->post(route('vouchers.purchase', $voucherId))
            ->assertSessionHasErrors('voucher');

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'saldo' => 10000]);
    }

    public function test_referral_signup_is_counted_toward_the_referral_reward(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'AIRA123']);

        $this->post(route('register.store'), [
            'name' => 'Teman Baru',
            'username' => 'teman-baru',
            'email' => 'teman@example.com',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'referral_code' => 'AIRA123',
            'terms' => '1',
            'captcha' => '1',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'email' => 'teman@example.com',
            'referred_by' => $referrer->id,
        ]);

        $this->actingAs($referrer)
            ->get(route('vouchers.index'))
            ->assertOk()
            ->assertSee('Ajak dan bagikan ke 5 teman')
            ->assertSee('1 / 5');
    }
}
