<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_purchase_order_then_receive_stock(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $supplier = Supplier::factory()->create();
        $product = $this->productWithStock(10);

        $this->actingAs($owner)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_cost' => 2000,
            ]],
        ])->assertRedirect();

        $purchase = Purchase::query()->first();
        $this->assertSame(PurchaseStatus::Ordered, $purchase->status);
        $this->assertEquals(10, (float) $product->stock()->value('quantity'));

        $this->actingAs($owner)
            ->post(route('purchases.receive', $purchase))
            ->assertRedirect();

        $this->assertSame(PurchaseStatus::Received, $purchase->fresh()->status);
        $this->assertEquals(15, (float) $product->stock()->value('quantity'));
    }

    public function test_owner_can_return_goods_to_supplier_and_reduce_stock(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $supplier = Supplier::factory()->create();
        $product = $this->productWithStock(10);

        $this->actingAs($owner)->post(route('purchase-returns.store'), [
            'supplier_id' => $supplier->id,
            'reason' => 'Barang rusak',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_cost' => 2000,
            ]],
        ])->assertRedirect(route('purchase-returns.index'));

        $this->assertEquals(7, (float) $product->stock()->value('quantity'));
    }
}
