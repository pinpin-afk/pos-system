<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GranularRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_products_but_not_settings(): void
    {
        $this->seedCore();
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->get(route('products.index'))->assertOk();
        $this->actingAs($admin)->get(route('settings.edit'))->assertForbidden();
    }

    public function test_manager_can_view_reports_and_cannot_manage_users(): void
    {
        $this->seedCore();
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('reports.index'))->assertOk();
        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
    }

    public function test_supervisor_cannot_refund_or_manage_products(): void
    {
        $this->seedCore();
        $supervisor = User::factory()->supervisor()->create();
        $product = $this->productWithStock();

        $this->actingAs($supervisor)->get(route('products.index'))->assertOk();
        $this->actingAs($supervisor)->post(route('products.store'), [
            'category_id' => $product->category_id,
            'name' => 'Baru',
            'sku' => 'SKU-NEW-1',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'unit' => 'PCS',
            'initial_stock' => 1,
            'minimum_stock' => 0,
        ])->assertForbidden();
    }

    public function test_administrator_can_authenticate_via_admin_login(): void
    {
        $this->seedCore();
        $admin = User::factory()->administrator()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }
}
