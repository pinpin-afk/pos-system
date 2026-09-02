<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_cashier_login_screen_can_be_rendered(): void
    {
        $this->get('/kasir/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/CashierLogin'));
    }

    public function test_guest_home_renders_login_portal(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Portal'));
    }

    public function test_owner_can_authenticate_via_admin_login_and_is_redirected_to_dashboard(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->post('/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_cashier_can_authenticate_via_cashier_login_and_is_redirected_to_pos(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->post('/kasir/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('pos.index'));
    }

    public function test_cashier_cannot_authenticate_via_admin_login(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_owner_cannot_authenticate_via_cashier_login(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->post('/kasir/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_to_either_portal(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->inactive()->create();
        $cashier = User::factory()->cashier()->inactive()->create();

        $this->post('/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->post('/kasir/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_visiting_pos_is_redirected_to_cashier_login(): void
    {
        $this->get('/pos')->assertRedirect(route('cashier.login'));
    }

    public function test_guest_visiting_dashboard_is_redirected_to_admin_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
