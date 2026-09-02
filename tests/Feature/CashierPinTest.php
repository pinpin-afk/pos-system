<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_login_with_pin(): void
    {
        $this->seedCore();
        User::factory()->cashier()->create([
            'pin' => '123456',
        ]);

        $this->post(route('cashier.login.store'), [
            'mode' => 'pin',
            'pin' => '123456',
        ])->assertRedirect(route('pos.index'));

        $this->assertAuthenticated();
    }

    public function test_cashier_can_login_with_employee_card(): void
    {
        $this->seedCore();
        User::factory()->cashier()->create([
            'card_number' => 'EMP-001',
        ]);

        $this->post(route('cashier.login.store'), [
            'mode' => 'card',
            'card_number' => 'EMP-001',
        ])->assertRedirect(route('pos.index'));

        $this->assertAuthenticated();
    }

    public function test_owner_can_enable_two_factor_authentication(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('profile.two-factor.enable'))
            ->assertRedirect();

        $this->assertNotNull($owner->fresh()->two_factor_secret);
    }

    public function test_wrong_pin_does_not_authenticate_cashier(): void
    {
        $this->seedCore();
        User::factory()->cashier()->create(['pin' => '123456']);

        $this->post(route('cashier.login.store'), [
            'mode' => 'pin',
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }
}
