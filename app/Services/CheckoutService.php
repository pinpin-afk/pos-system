<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\LocationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private StockService $stockService,
        private InvoiceNumberService $invoiceNumberService,
        private PromotionService $promotionService,
        private LocationContext $locationContext,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function checkout(User $user, CashierShift $shift, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $shift, $payload) {
            $settings = StoreSetting::current();
            $branch = $this->locationContext->branch($user);
            $warehouse = $this->locationContext->warehouse($user);
            $customer = $this->customerForBranch($payload, $branch?->id);
            $totals = $this->calculate($payload['items'], $payload, $settings);
            $payments = $this->paymentsWithRedeemedPoints(
                $this->normalizePayments($payload),
                $totals['points_redeemed'],
            );
            $this->assertPayments($payments, $totals['grand_total']);

            $sale = $this->resolveSale($payload, $user, $shift);

            $sale->fill([
                'invoice_number' => $this->invoiceNumberService->next(),
                'cashier_id' => $user->id,
                'customer_id' => $customer->id,
                'cashier_shift_id' => $shift->id,
                'branch_id' => $branch?->id,
                'warehouse_id' => $warehouse?->id,
                'subtotal' => $totals['subtotal'],
                'discount_type' => $totals['discount_type'],
                'discount_value' => $totals['discount_value'],
                'discount_amount' => $totals['discount_amount'],
                'tax' => $totals['tax'],
                'tax_rate' => $settings->tax_rate,
                'grand_total' => $totals['grand_total'],
                'cost_total' => $totals['cost_total'],
                'profit' => $totals['profit'],
                'points_redeemed' => $totals['points_redeemed'],
                'voucher_code' => filled($payload['voucher_code'] ?? null)
                    ? strtoupper(trim((string) $payload['voucher_code']))
                    : null,
                'status' => SaleStatus::Completed,
                'held_at' => null,
                'completed_at' => now(),
            ]);
            $sale->save();

            $sale->items()->delete();
            $sale->payments()->delete();

            foreach ($totals['lines'] as $line) {
                $sale->items()->create($line['attributes']);
                $this->stockService->decreaseForSale(
                    $line['product'],
                    $line['quantity'],
                    $sale,
                    $user,
                    $line['variant'],
                    $warehouse,
                );
            }

            foreach ($payments as $payment) {
                $method = PaymentMethod::from($payment['method']);
                $amount = (float) $payment['amount'];
                $tendered = $method === PaymentMethod::Cash
                    ? (float) ($payment['tendered'] ?? $amount)
                    : $amount;

                Payment::query()->create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'label' => $payment['label'] ?? null,
                    'amount' => $amount,
                    'tendered' => $tendered,
                    'change_amount' => $method === PaymentMethod::Cash
                        ? round(max($tendered - $amount, 0), 2)
                        : 0,
                    'reference_number' => $payment['reference_number'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            $this->promotionService->redeemVoucher($payload['voucher_code'] ?? null);
            $this->settleLoyalty($sale, $totals['points_redeemed'], $settings);

            $this->activityLogger->log($user, 'sale.completed', $sale, [
                'invoice' => $sale->invoice_number,
                'grand_total' => $sale->grand_total,
            ]);

            return $sale->load(['items', 'customer', 'cashier', 'payments']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hold(User $user, CashierShift $shift, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $shift, $payload) {
            $settings = StoreSetting::current();
            $branchId = $this->locationContext->branch($user)?->id;
            $customer = $this->customerForBranch($payload, $branchId);
            $totals = $this->calculate($payload['items'], $payload, $settings);

            $sale = $this->resolveSale($payload, $user, $shift);
            $sale->fill([
                'invoice_number' => null,
                'cashier_id' => $user->id,
                'customer_id' => $customer->id,
                'cashier_shift_id' => $shift->id,
                'branch_id' => $this->locationContext->branch($user)?->id,
                'warehouse_id' => $this->locationContext->warehouse($user)?->id,
                'subtotal' => $totals['subtotal'],
                'discount_type' => $totals['discount_type'],
                'discount_value' => $totals['discount_value'],
                'discount_amount' => $totals['discount_amount'],
                'tax' => $totals['tax'],
                'tax_rate' => $settings->tax_rate,
                'grand_total' => $totals['grand_total'],
                'cost_total' => $totals['cost_total'],
                'profit' => $totals['profit'],
                'status' => SaleStatus::Held,
                'held_at' => now(),
                'completed_at' => null,
            ]);
            $sale->save();

            $sale->items()->delete();

            foreach ($totals['lines'] as $line) {
                $sale->items()->create($line['attributes']);
            }

            return $sale->load(['items', 'customer']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function calculate(array $items, array $payload, StoreSetting $settings): array
    {
        $lines = [];
        $subtotal = 0.0;
        $costTotal = 0.0;
        $allowDiscount = (bool) $settings->allow_discount;

        foreach ($items as $row) {
            $product = Product::query()
                ->with(['stock', 'variants'])
                ->lockForUpdate()
                ->findOrFail($row['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Produk {$product->name} tidak aktif.",
                ]);
            }

            $variant = null;
            $sellingPrice = (float) $product->selling_price;
            $purchasePrice = (float) $product->purchase_price;
            $sku = $product->sku;
            $productName = $product->name;
            $variantName = null;

            if (! empty($row['product_variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->whereKey($row['product_variant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $variant->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Varian {$variant->name} tidak aktif.",
                    ]);
                }

                $sellingPrice = (float) $variant->selling_price;
                $purchasePrice = (float) $variant->purchase_price;
                $sku = $variant->sku;
                $variantName = $variant->name;
            } elseif ($product->variants->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => "Pilih varian untuk {$product->name}.",
                ]);
            }

            $quantity = (float) $row['quantity'];
            $lineGross = round($sellingPrice * $quantity, 2);
            $itemDiscountType = $allowDiscount ? ($row['discount_type'] ?? null) : null;
            $itemDiscountValue = $allowDiscount ? (float) ($row['discount_value'] ?? 0) : 0;
            $itemDiscount = $this->discountAmount($lineGross, $itemDiscountType, $itemDiscountValue);
            $lineSubtotal = round($lineGross - $itemDiscount, 2);
            $lineCost = round($purchasePrice * $quantity, 2);

            $lines[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'attributes' => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $productName,
                    'variant_name' => $variantName,
                    'sku' => $sku,
                    'quantity' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'discount_type' => $itemDiscountType,
                    'discount_value' => $itemDiscountValue,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $lineSubtotal,
                    'profit' => round($lineSubtotal - $lineCost, 2),
                ],
            ];

            $subtotal += $lineSubtotal;
            $costTotal += $lineCost;
        }

        $subtotal = round($subtotal, 2);
        $costTotal = round($costTotal, 2);
        $discountType = $allowDiscount ? ($payload['discount_type'] ?? null) : null;
        $discountValue = $allowDiscount ? (float) ($payload['discount_value'] ?? 0) : 0;
        $manualDiscount = $this->discountAmount($subtotal, $discountType, $discountValue);

        $customer = ! empty($payload['customer_id'])
            ? Customer::query()->find($payload['customer_id'])
            : null;

        $promo = $this->promotionService->autoDiscount($lines, $subtotal, $customer);
        $voucherAmount = $this->promotionService->voucherAmount($payload['voucher_code'] ?? null, $subtotal);
        $discountAmount = round(min($subtotal, $manualDiscount + $promo['amount'] + $voucherAmount), 2);
        $afterDiscount = round($subtotal - $discountAmount, 2);
        $taxRate = (float) $settings->tax_rate;

        if ($settings->tax_inclusive) {
            $netSales = $taxRate > 0
                ? round($afterDiscount / (1 + ($taxRate / 100)), 2)
                : $afterDiscount;
            $tax = round($afterDiscount - $netSales, 2);
            $grandTotal = $afterDiscount;
            $profit = round($netSales - $costTotal, 2);
        } else {
            $tax = round($afterDiscount * ($taxRate / 100), 2);
            $grandTotal = round($afterDiscount + $tax, 2);
            $profit = round($afterDiscount - $costTotal, 2);
        }

        $requestedPoints = (int) ($payload['redeem_points'] ?? 0);
        $redeemedPoints = 0;

        if ($requestedPoints > 0) {
            $this->promotionService->redeemPoints($requestedPoints, $settings);

            if ($customer === null || $customer->is_walk_in) {
                throw ValidationException::withMessages([
                    'redeem_points' => 'Hanya member yang bisa menukar poin.',
                ]);
            }

            if ((int) $customer->points < $requestedPoints) {
                throw ValidationException::withMessages([
                    'redeem_points' => 'Poin pelanggan tidak cukup.',
                ]);
            }

            $redeemedPoints = min($requestedPoints, (int) floor(max(0, $grandTotal)));
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'cost_total' => $costTotal,
            'profit' => $profit,
            'promo_label' => $promo['label'],
            'points_redeemed' => $redeemedPoints,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function customerForBranch(array $payload, ?int $branchId): Customer
    {
        if (! empty($payload['customer_id'])) {
            $customer = Customer::query()->find($payload['customer_id']);

            if ($customer === null || $customer->branch_id === null || (int) $customer->branch_id !== (int) $branchId) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Pelanggan tidak terdaftar di cabang ini.',
                ]);
            }

            return $customer;
        }

        return Customer::ensureWalkIn($branchId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSale(array $payload, User $user, CashierShift $shift): Sale
    {
        if (! empty($payload['held_sale_id'])) {
            return Sale::query()
                ->whereKey($payload['held_sale_id'])
                ->where('status', SaleStatus::Held)
                ->where('cashier_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return new Sale([
            'cashier_id' => $user->id,
            'cashier_shift_id' => $shift->id,
        ]);
    }

    private function discountAmount(float $base, ?string $type, float $value): float
    {
        if ($value <= 0 || $type === null || $type === '') {
            return 0;
        }

        $discountType = DiscountType::from($type);

        $amount = $discountType === DiscountType::Percent
            ? round($base * ($value / 100), 2)
            : round($value, 2);

        if ($amount > $base) {
            throw ValidationException::withMessages([
                'discount_value' => 'Diskon tidak boleh melebihi subtotal.',
            ]);
        }

        return $amount;
    }

    /**
     * @param  list<array<string, mixed>>  $payments
     * @return list<array<string, mixed>>
     */
    private function paymentsWithRedeemedPoints(array $payments, int $redeemedPoints): array
    {
        $payments = array_values(array_filter(
            $payments,
            function (array $payment): bool {
                $method = PaymentMethod::tryFrom((string) ($payment['method'] ?? ''));

                return $method !== null
                    && $method !== PaymentMethod::Points
                    && (float) ($payment['amount'] ?? 0) > 0.01;
            },
        ));

        if ($redeemedPoints > 0) {
            $payments[] = [
                'method' => PaymentMethod::Points->value,
                'amount' => (float) $redeemedPoints,
            ];
        }

        return $payments;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizePayments(array $payload): array
    {
        if (! empty($payload['payments']) && is_array($payload['payments'])) {
            return array_values($payload['payments']);
        }

        if (! empty($payload['payment']) && is_array($payload['payment'])) {
            return [$payload['payment']];
        }

        throw ValidationException::withMessages([
            'payment' => 'Pembayaran wajib diisi.',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $payments
     */
    private function assertPayments(array $payments, float $grandTotal): void
    {
        if ($grandTotal <= 0.01) {
            return;
        }

        $allocated = 0.0;

        foreach ($payments as $index => $payment) {
            $method = PaymentMethod::from($payment['method']);
            $amount = (float) ($payment['amount'] ?? 0);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    "payments.{$index}.amount" => 'Nominal pembayaran harus lebih dari 0.',
                ]);
            }

            if ($method === PaymentMethod::Cash) {
                $tendered = (float) ($payment['tendered'] ?? 0);

                if ($tendered + 0.0001 < $amount) {
                    throw ValidationException::withMessages([
                        count($payments) === 1 ? 'payment.tendered' : "payments.{$index}.tendered" => 'Nominal bayar kurang dari porsi tunai.',
                    ]);
                }
            }

            $allocated += $amount;
        }

        if (abs($allocated - $grandTotal) > 0.01) {
            throw ValidationException::withMessages([
                'payment.amount' => 'Jumlah pembayaran harus sama dengan total.',
            ]);
        }
    }

    private function settleLoyalty(Sale $sale, int $redeemed, StoreSetting $settings): void
    {
        $customer = $sale->customer()->lockForUpdate()->first();

        if ($customer === null || $customer->is_walk_in) {
            return;
        }

        if ($redeemed > 0) {
            $customer->decrement('points', $redeemed);
        }

        $points = $settings->pointsEarnedFor(max(0.0, (float) $sale->grand_total - $redeemed));

        if ($points > 0) {
            $customer->increment('points', $points);
        }
    }
}
