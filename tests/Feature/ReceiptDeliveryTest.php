<?php

namespace Tests\Feature;

use App\Mail\SaleReceiptMail;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReceiptDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_sale_receipt_can_be_emailed(): void
    {
        Mail::fake();
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::factory()->create(['email' => 'member@pos.test']);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 8000, 'tendered' => 8000],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->first();

        $this->actingAs($cashier)->post(route('receipts.email', $sale), [
            'email' => 'member@pos.test',
        ])->assertRedirect();

        Mail::assertSent(SaleReceiptMail::class, function (SaleReceiptMail $mail) use ($sale): bool {
            return $mail->sale->is($sale) && $mail->hasTo('member@pos.test');
        });
    }
}
