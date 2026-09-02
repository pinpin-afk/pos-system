<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private DocumentNumberService $documentNumberService,
        private StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $user, array $payload): Purchase
    {
        return DB::transaction(function () use ($user, $payload) {
            $items = $this->normalizedItems($payload['items']);

            $purchase = Purchase::query()->create([
                'number' => $this->documentNumberService->next('PO', Purchase::class),
                'supplier_id' => $payload['supplier_id'],
                'user_id' => $user->id,
                'status' => PurchaseStatus::Ordered,
                'subtotal' => collect($items)->sum('subtotal'),
                'ordered_at' => now(),
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $purchase->items()->create($item);
            }

            return $purchase->load(['supplier', 'items.product']);
        });
    }

    public function receive(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $purchase->load('items.product');

            if ($purchase->status !== PurchaseStatus::Ordered) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya pesanan yang belum diterima yang bisa diterima.',
                ]);
            }

            foreach ($purchase->items as $item) {
                $variant = $item->product_variant_id
                    ? ProductVariant::query()->find($item->product_variant_id)
                    : null;

                $this->stockService->increaseForPurchase(
                    $item->product,
                    (float) $item->quantity,
                    $purchase,
                    $user,
                    $variant,
                );

                $item->update(['received_quantity' => $item->quantity]);
            }

            $purchase->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            return $purchase->fresh(['supplier', 'items.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function returnToSupplier(User $user, array $payload): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $payload) {
            $items = $this->normalizedItems($payload['items']);

            $return = PurchaseReturn::query()->create([
                'number' => $this->documentNumberService->next('RTN', PurchaseReturn::class),
                'purchase_id' => $payload['purchase_id'] ?? null,
                'supplier_id' => $payload['supplier_id'],
                'user_id' => $user->id,
                'status' => 'completed',
                'subtotal' => collect($items)->sum('subtotal'),
                'reason' => $payload['reason'],
                'returned_at' => now(),
            ]);

            foreach ($items as $item) {
                $return->items()->create($item);

                $product = Product::query()->findOrFail($item['product_id']);
                $variant = isset($item['product_variant_id'])
                    ? ProductVariant::query()->find($item['product_variant_id'])
                    : null;

                $this->stockService->decreaseForReturn(
                    $product,
                    (float) $item['quantity'],
                    $return,
                    $user,
                    $variant,
                );
            }

            return $return->load(['supplier', 'items.product']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizedItems(array $items): array
    {
        return collect($items)->map(function (array $row) {
            $quantity = (float) $row['quantity'];
            $unitCost = (float) $row['unit_cost'];

            return [
                'product_id' => $row['product_id'],
                'product_variant_id' => $row['product_variant_id'] ?? null,
                'quantity' => $quantity,
                'received_quantity' => 0,
                'unit_cost' => $unitCost,
                'subtotal' => round($quantity * $unitCost, 2),
            ];
        })->all();
    }
}
