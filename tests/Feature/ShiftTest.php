<?php

namespace Tests\Feature;

use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_shift_calculates_expected_cash_and_difference(): void
    {
        $this->seedCore();

        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier)->post(route('shifts.store'), [
            'opening_cash' => 500000,
        ])->assertRedirect(route('pos.index'));

        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 20000],
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('shifts.cash-movements.store'), [
            'type' => CashMovementType::Out->value,
            'amount' => 10000,
            'reason' => 'Beli air galon',
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('shifts.close.store'), [
            'actual_cash' => 505000,
        ])->assertRedirect();

        $shift = CashierShift::query()->first();

        $this->assertSame(ShiftStatus::Closed, $shift->status);
        $this->assertEquals(506000, (float) $shift->expected_cash);
        $this->assertEquals(-1000, (float) $shift->difference);
    }
}
