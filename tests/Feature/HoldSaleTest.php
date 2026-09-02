<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_hold_does_not_reduce_stock_until_checkout(): void
    {
        $this->seedCore();

        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.hold'), [
            'customer_id' => $customer->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
        ])->assertRedirect(route('pos.index'));

        $this->assertEquals(10, (float) $product->stock()->value('quantity'));
        $held = Sale::query()->held()->first();
        $this->assertNotNull($held);
        $this->assertNull($held->invoice_number);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'held_sale_id' => $held->id,
            'customer_id' => $customer->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
            'payment' => [
                'method' => 'qris',
                'amount' => 16000,
            ],
        ])->assertRedirect();

        $this->assertEquals(8, (float) $product->stock()->value('quantity'));
        $this->assertTrue($held->fresh()->isCompleted());
    }
}
