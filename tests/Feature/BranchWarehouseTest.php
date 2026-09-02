<?php

namespace Tests\Feature;

use App\Enums\TenantPlan;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchWarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_branch_with_default_warehouse(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('branches.store'), [
            'name' => 'Cabang Bandung',
            'code' => 'BDG',
        ])->assertRedirect(route('branches.index'));

        $branch = Branch::query()->where('code', 'BDG')->first();
        $this->assertNotNull($branch);
        $this->assertSame('Gudang Utama', $branch->warehouses()->where('is_default', true)->value('name'));
        $this->assertNotNull(Customer::walkInFor($branch->id));
    }

    public function test_starter_plan_rejects_second_branch(): void
    {
        $this->seedCore();
        Tenant::query()->first()->update(['plan' => TenantPlan::Starter]);
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('branches.store'), [
            'name' => 'Cabang Kedua',
        ])->assertSessionHasErrors('name');
    }

    public function test_owner_can_create_warehouse_for_branch(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $branch = Branch::query()->first();

        $this->actingAs($owner)->post(route('warehouses.store'), [
            'branch_id' => $branch->id,
            'name' => 'Gudang Display',
        ])->assertRedirect(route('warehouses.index'));

        $this->assertTrue(Warehouse::query()->where('name', 'Gudang Display')->exists());
    }

    public function test_cashier_cannot_manage_branches(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('branches.index'))->assertForbidden();
    }
}
