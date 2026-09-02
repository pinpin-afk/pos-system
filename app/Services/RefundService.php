<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private DocumentNumberService $documentNumberService,
        private StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function refund(Sale $sale, User $user, array $payload): Refund
    {
        return DB::transaction(function () use ($sale, $user, $payload) {
            $sale = Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->with(['items.product', 'items.variant', 'customer'])
                ->firstOrFail();

            if (! $sale->canRefund()) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi ini tidak bisa direfund.',
                ]);
            }

            $lines = $payload['items'];
            $amount = 0.0;

            foreach ($lines as $line) {
                $item = $sale->items->firstWhere('id', (int) $line['sale_item_id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Item refund tidak ditemukan pada transaksi.',
                    ]);
                }

                $alreadyRefunded = (float) $item->refunds()->sum('quantity');
                $remaining = (float) $item->quantity - $alreadyRefunded;
                $qty = (float) $line['quantity'];

                if ($qty > $remaining + 0.0001) {
                    throw ValidationException::withMessages([
                        'items' => "Qty refund {$item->product_name} melebihi sisa item.",
                    ]);
                }

                $ratio = (float) $item->quantity > 0 ? $qty / (float) $item->quantity : 0;
                $amount += round((float) $item->subtotal * $ratio, 2);
            }

            $refund = Refund::query()->create([
                'number' => $this->documentNumberService->next('RFD', Refund::class),
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'amount' => round($amount, 2),
                'reason' => $payload['reason'],
            ]);

            foreach ($lines as $line) {
                /** @var SaleItem $item */
                $item = $sale->items->firstWhere('id', (int) $line['sale_item_id']);
                $qty = (float) $line['quantity'];
                $ratio = (float) $item->quantity > 0 ? $qty / (float) $item->quantity : 0;
                $lineAmount = round((float) $item->subtotal * $ratio, 2);

                $refund->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $qty,
                    'amount' => $lineAmount,
                ]);

                $this->stockService->increaseForRefund(
                    $item->product,
                    $qty,
                    $refund,
                    $user,
                    $item->variant,
                );
            }

            $refundedTotal = round((float) $sale->refunded_amount + $amount, 2);
            $sale->update([
                'refunded_amount' => $refundedTotal,
                'status' => $refundedTotal + 0.009 >= (float) $sale->grand_total
                    ? SaleStatus::Refunded
                    : SaleStatus::PartiallyRefunded,
            ]);

            if ($sale->customer && ! $sale->customer->is_walk_in) {
                $points = StoreSetting::current()->pointsForSpend($amount);
                if ($points > 0) {
                    $sale->customer->decrement('points', min($points, (int) $sale->customer->points));
                }
            }

            return $refund->load('items');
        });
    }

    public function void(Sale $sale, User $user, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason) {
            $sale = Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->with(['items.product', 'items.variant', 'customer'])
                ->firstOrFail();

            if (! $sale->canVoid()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya transaksi selesai tanpa refund yang bisa di-void.',
                ]);
            }

            foreach ($sale->items as $item) {
                $this->stockService->increaseForRefund(
                    $item->product,
                    (float) $item->quantity,
                    $sale,
                    $user,
                    $item->variant,
                );
            }

            $sale->update([
                'status' => SaleStatus::Voided,
                'voided_by' => $user->id,
                'void_reason' => $reason,
                'voided_at' => now(),
            ]);

            if ($sale->customer && ! $sale->customer->is_walk_in) {
                $points = StoreSetting::current()->pointsForSpend((float) $sale->grand_total);
                if ($points > 0) {
                    $sale->customer->decrement('points', min($points, (int) $sale->customer->points));
                }
            }

            return $sale->fresh(['items', 'customer']);
        });
    }
}
