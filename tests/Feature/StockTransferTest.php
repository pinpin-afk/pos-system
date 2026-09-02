<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_transfer_stock_and_receive_it_in_destination_warehouse(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $from = Warehouse::query()->first();
        $to = Warehouse::factory()->create([
            'branch_id' => $from->branch_id,
            'is_default' => false,
        ]);
        $product = $this->productWithStock(10);

        $this->actingAs($owner)->post(route('transfers.store'), [
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 3,
            ]],
        ])->assertRedirect(route('transfers.index'));

        $transfer = StockTransfer::query()->first();
        $this->assertSame(TransferStatus::Pending, $transfer->status);
        $this->assertEquals(7, (float) Stock::query()->where('product_id', $product->id)->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertSame(1, $transfer->items()->count());

        $this->actingAs($owner)->post(route('transfers.receive', $transfer))->assertRedirect();

        $this->assertSame(TransferStatus::Received, $transfer->fresh()->status);
        $this->assertEquals(3, (float) Stock::query()->where('product_id', $product->id)->where('warehouse_id', $to->id)->value('quantity'));
        $this->assertTrue(
            StockMovement::query()->where('type', StockMovementType::TransferOut)->exists()
        );
    }

    public function test_cashier_cannot_create_stock_transfer(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        $warehouses = Warehouse::query()->get();

        $this->actingAs($cashier)->post(route('transfers.store'), [
            'from_warehouse_id' => $warehouses->first()->id,
            'to_warehouse_id' => $warehouses->first()->id,
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ])->assertForbidden();
    }
}
