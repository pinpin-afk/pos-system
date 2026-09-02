<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PromotionType;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoyaltyPromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_reduces_checkout_total_and_increments_usage(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();
        $voucher = Voucher::factory()->create([
            'code' => 'HEMAT5',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 5000,
            'max_uses' => 2,
        ]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'voucher_code' => 'HEMAT5',
            'payment' => ['method' => 'cash', 'amount' => 11000, 'tendered' => 11000],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->first();
        $this->assertEquals(11000, (float) $sale->grand_total);
        $this->assertSame('HEMAT5', $sale->voucher_code);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_member_can_redeem_points_as_rupiah(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 5000]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'redeem_points' => 5000,
            'payment' => ['method' => 'cash', 'amount' => 11000, 'tendered' => 11000],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->with('payments')->first();
        $this->assertEquals(16000, (float) $sale->grand_total);
        $this->assertSame(5000, $sale->points_redeemed);
        $this->assertSame(1100, $member->fresh()->points);
        $this->assertSame(2, $sale->payments->count());
        $this->assertEquals(11000, (float) $sale->payments->firstWhere('method', PaymentMethod::Cash)->amount);
        $this->assertEquals(5000, (float) $sale->payments->firstWhere('method', PaymentMethod::Points)->amount);
    }

    public function test_member_can_pay_entire_bill_with_points(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 20000]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'redeem_points' => 16000,
            'payment' => ['method' => 'cash', 'amount' => 0, 'tendered' => 0],
        ])->assertRedirect();

        $sale = Sale::query()->completed()->with('payments')->first();
        $this->assertEquals(16000, (float) $sale->grand_total);
        $this->assertSame(16000, $sale->points_redeemed);
        $this->assertSame(4000, $member->fresh()->points);
        $this->assertSame(1, $sale->payments->count());
        $this->assertSame(PaymentMethod::Points, $sale->payments->first()->method);
        $this->assertEquals(16000, (float) $sale->payments->first()->amount);

        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('payments', 1)
                ->where('payments.0.method', PaymentMethod::Points->value)
            );

        $this->actingAs($owner)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('sales.data.0.payments.0.method', PaymentMethod::Points->value)
            );
    }

    public function test_checkout_rejects_client_sent_points_payment_method(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 20000]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'points', 'amount' => 16000],
        ])->assertSessionHasErrors('payment.method');
    }

    public function test_active_percent_promotion_is_applied_automatically(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();
        Promotion::factory()->create([
            'type' => PromotionType::Percent,
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 14400, 'tendered' => 14400],
        ])->assertRedirect();

        $this->assertEquals(14400, (float) Sale::query()->completed()->value('grand_total'));
    }

    public function test_member_earns_points_from_purchase_using_loyalty_settings(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 0]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 16000],
        ])->assertRedirect();

        $this->assertSame(1600, $member->fresh()->points);
    }

    public function test_custom_loyalty_rate_awards_configured_points(): void
    {
        $this->seedCore();
        StoreSetting::query()->first()->update([
            'loyalty_enabled' => true,
            'loyalty_earn_points' => 2,
            'loyalty_spend_amount' => 5000,
        ]);
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 0]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 16000],
        ])->assertRedirect();

        $this->assertSame(6, $member->fresh()->points);
    }

    public function test_walk_in_customer_does_not_earn_points(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $customer = Customer::query()->walkIn()->first();

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 16000],
        ])->assertRedirect();

        $this->assertSame(0, (int) $customer->fresh()->points);
    }

    public function test_disabled_loyalty_does_not_award_or_redeem_points(): void
    {
        $this->seedCore();
        StoreSetting::query()->first()->update(['loyalty_enabled' => false]);
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock(10);
        $member = Customer::factory()->create(['points' => 100]);

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'redeem_points' => 100,
            'payment' => ['method' => 'cash', 'amount' => 6000, 'tendered' => 6000],
        ])->assertSessionHasErrors('redeem_points');

        $this->actingAs($cashier)->post(route('pos.checkout'), [
            'customer_id' => $member->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => 'cash', 'amount' => 16000, 'tendered' => 16000],
        ])->assertRedirect();

        $this->assertSame(100, $member->fresh()->points);
    }

    public function test_owner_can_update_loyalty_settings(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $setting = StoreSetting::query()->first();

        $this->actingAs($owner)->put(route('settings.update'), [
            'store_name' => $setting->store_name,
            'tax_rate' => $setting->tax_rate,
            'invoice_prefix' => $setting->invoice_prefix,
            'timezone' => $setting->timezone,
            'currency' => $setting->currency,
            'loyalty_enabled' => true,
            'loyalty_earn_points' => 1000,
            'loyalty_spend_amount' => 25000,
        ])->assertRedirect();

        $setting->refresh();
        $this->assertTrue($setting->loyalty_enabled);
        $this->assertSame(1000, $setting->loyalty_earn_points);
        $this->assertSame('25000.00', $setting->loyalty_spend_amount);
    }

    public function test_owner_can_disable_loyalty_program(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $setting = StoreSetting::query()->first();

        $this->actingAs($owner)->put(route('settings.update'), [
            'store_name' => $setting->store_name,
            'tax_rate' => $setting->tax_rate,
            'invoice_prefix' => $setting->invoice_prefix,
            'timezone' => $setting->timezone,
            'currency' => $setting->currency,
            'loyalty_enabled' => 0,
            'loyalty_earn_points' => $setting->loyalty_earn_points,
            'loyalty_spend_amount' => $setting->loyalty_spend_amount,
            'loyalty_redeem_points' => $setting->loyalty_redeem_points,
            'loyalty_redeem_amount' => $setting->loyalty_redeem_amount,
        ])->assertRedirect();

        $this->assertFalse($setting->fresh()->loyalty_enabled);
    }
}
