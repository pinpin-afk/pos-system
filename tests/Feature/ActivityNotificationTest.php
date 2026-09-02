<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CashDifferenceNotification;
use App\Notifications\StockAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_writes_activity_log_and_notifies_when_stock_hits_minimum(): void
    {
        Notification::fake();
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(3);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['method' => 'cash', 'amount' => 8000, 'tendered' => 8000],
        ])->assertRedirect();

        $this->assertTrue(ActivityLog::query()->where('action', 'sale.completed')->exists());
        Notification::assertSentTo($owner, StockAlertNotification::class);
    }

    public function test_closing_shift_with_cash_difference_notifies_owner(): void
    {
        Notification::fake();
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->post(route('shifts.store'), [
            'opening_cash' => 100000,
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('shifts.close.store'), [
            'actual_cash' => 99000,
        ])->assertRedirect();

        Notification::assertSentTo($owner, CashDifferenceNotification::class);
    }
}
