<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_reduces_stock_records_payment_and_profit(): void
    {
        $this->seedCore();

        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create([
            'user_id' => $cashier->id,
            'opening_cash' => 500000,
        ]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $response = $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
            'payment' => [
                'method' => 'cash',
                'amount' => 16000,
                'tendered' => 20000,
            ],
        ]);

        $sale = Sale::query()->completed()->first();

        $response->assertRedirect(route('receipts.show', $sale));
        $this->assertSame('INV-'.now()->format('Ymd').'-00001', $sale->invoice_number);
        $this->assertEquals(16000, (float) $sale->grand_total);
        $this->assertEquals(10000, (float) $sale->cost_total);
        $this->assertEquals(6000, (float) $sale->profit);
        $this->assertEquals(8, (float) $product->stock()->value('quantity'));
        $this->assertSame(1, StockMovement::query()->where('type', StockMovementType::Sale)->count());
        $this->assertEquals(4000, (float) $sale->payment->change_amount);
    }

    public function test_invoice_numbers_are_unique_and_sequential(): void
    {
        $this->seedCore();

        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $payload = [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 8000, 'tendered' => 8000],
        ];

        $this->actingAs($cashier)->post(route('pos.checkout'), $payload)->assertRedirect();
        $this->actingAs($cashier)->post(route('pos.checkout'), $payload)->assertRedirect();

        $this->assertSame([
            'INV-'.now()->format('Ymd').'-00001',
            'INV-'.now()->format('Ymd').'-00002',
        ], Sale::query()->orderBy('id')->pluck('invoice_number')->all());
    }

    public function test_pos_is_blocked_when_shift_is_not_open(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertRedirect(route('shifts.open'));
    }

    public function test_checkout_rejects_a_member_from_another_branch(): void
    {
        $this->seedCore();

        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $otherBranch = Branch::factory()->create(['tenant_id' => $cashier->tenant_id]);
        $foreignMember = Customer::factory()->create([
            'branch_id' => $otherBranch->id,
            'name' => 'Member Bandung',
        ]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $foreignMember->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
            'payment' => [
                'method' => 'cash',
                'amount' => 8000,
                'tendered' => 8000,
            ],
        ])->assertSessionHasErrors(['customer_id' => 'Pelanggan tidak terdaftar di cabang ini.']);

        $this->assertSame(0, Sale::query()->count());
    }
}
