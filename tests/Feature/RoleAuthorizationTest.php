<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_product_management(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('products.index'))
            ->assertForbidden();
    }

    public function test_owner_can_access_product_management(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('products.index'))
            ->assertOk();
    }
}
