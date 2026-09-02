<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_accepts_split_payments_that_cover_the_total(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payments' => [
                ['method' => 'cash', 'amount' => 10000, 'tendered' => 10000],
                ['method' => 'ewallet', 'amount' => 6000, 'label' => 'OVO'],
            ],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->first();
        $this->assertEquals(16000, (float) $sale->grand_total);
        $this->assertSame(2, $sale->payments()->count());
        $this->assertSame(PaymentMethod::Ewallet, $sale->payments()->where('label', 'OVO')->first()->method);
    }

    public function test_split_payments_that_do_not_match_total_are_rejected(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payments' => [
                ['method' => 'cash', 'amount' => 5000, 'tendered' => 5000],
            ],
        ])->assertSessionHasErrors('payment.amount');
    }
}
