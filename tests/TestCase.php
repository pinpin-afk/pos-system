<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function seedCore(): void
    {
        $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
        $branch = Branch::query()->first() ?? Branch::factory()->create(['tenant_id' => $tenant->id]);

        if (Warehouse::query()->doesntExist()) {
            Warehouse::factory()->create([
                'branch_id' => $branch->id,
                'is_default' => true,
            ]);
        }

        StoreSetting::factory()->create(['tenant_id' => $tenant->id]);
        Customer::ensureWalkIn($branch->id);
    }

    protected function productWithStock(float $quantity = 10): Product
    {
        $warehouse = Warehouse::query()->orderBy('id')->first();

        return Product::factory()
            ->has(Stock::factory()->state([
                'warehouse_id' => $warehouse?->id,
                'quantity' => $quantity,
                'minimum_stock' => 2,
            ]))
            ->create();
    }
}
