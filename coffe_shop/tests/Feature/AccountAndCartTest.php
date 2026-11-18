<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'next' => 'home',
        ])->assertRedirect(route('catalog.index'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);

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
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
            'captcha' => '1',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);

        $this->post(route('login.store'), [
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
        ])->assertRedirect(route('catalog.index'));

        $this->assertAuthenticated();
    }

    public function test_registration_requires_the_robot_checkbox(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Kopi Customer',
            'email' => 'new@example.com',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
            'terms' => '1',
        ])->assertSessionHasErrors('captcha');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        $this->assertGuest();
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
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_locked_for_fifteen_seconds_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        foreach (range(1, 3) as $attempt) {
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'correct-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->travel(16)->seconds();
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('catalog.index'));

        $this->assertAuthenticatedAs($user);
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
