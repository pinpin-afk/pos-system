<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_refund_a_completed_sale_and_restore_stock(): void
    {
        $this->seedCore();

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 16000],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->first();

        $this->actingAs($owner)->post(route('sales.refund', $sale), [
            'reason' => 'Barang dikembalikan',
            'items' => [[
                'sale_item_id' => $sale->items()->first()->id,
                'quantity' => 2,
            ]],
        ])->assertRedirect();

        $this->assertSame(SaleStatus::Refunded, $sale->fresh()->status);
        $this->assertEquals(10, (float) $product->stock()->value('quantity'));
        $this->assertSame(1, StockMovement::query()->where('type', StockMovementType::Refund)->count());
    }

    public function test_owner_can_void_a_completed_sale_and_restore_stock(): void
    {
        $this->seedCore();

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 8000, 'tendered' => 8000],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->first();

        $this->actingAs($owner)->post(route('sales.void', $sale), [
            'reason' => 'Salah input',
        ])->assertRedirect();

        $this->assertSame(SaleStatus::Voided, $sale->fresh()->status);
        $this->assertEquals(10, (float) $product->stock()->value('quantity'));
    }

    public function test_supervisor_cannot_void_a_sale(): void
    {
        $this->seedCore();

        $supervisor = User::factory()->supervisor()->create();
        $sale = Sale::factory()->completed()->create();

        $this->actingAs($supervisor)->post(route('sales.void', $sale), [
            'reason' => 'Tidak boleh',
        ])->assertForbidden();
    }
}
