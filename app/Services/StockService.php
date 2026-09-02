<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\StockAlertNotification;
use App\Support\LocationContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(private LocationContext $locationContext) {}

    public function adjust(Product $product, float $delta, string $notes, User $user, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, $delta, StockMovementType::Adjustment, $user, $notes, $product, null, $warehouse);
    }

    public function recordInitial(Product $product, float $quantity, User $user, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, $quantity, StockMovementType::Initial, $user, 'Stok awal produk', $product, null, $warehouse);
    }

    public function decreaseForSale(Product $product, float $quantity, Model $reference, User $user, ?ProductVariant $variant = null, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, -$quantity, StockMovementType::Sale, $user, null, $reference, $variant, $warehouse);
    }

    public function increaseForRefund(Product $product, float $quantity, Model $reference, User $user, ?ProductVariant $variant = null, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, $quantity, StockMovementType::Refund, $user, 'Refund penjualan', $reference, $variant, $warehouse);
    }

    public function increaseForPurchase(Product $product, float $quantity, Model $reference, User $user, ?ProductVariant $variant = null, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, $quantity, StockMovementType::Purchase, $user, 'Penerimaan pembelian', $reference, $variant, $warehouse);
    }

    public function decreaseForReturn(Product $product, float $quantity, Model $reference, User $user, ?ProductVariant $variant = null, ?Warehouse $warehouse = null): StockMovement
    {
        return $this->move($product, -$quantity, StockMovementType::Return, $user, 'Retur ke supplier', $reference, $variant, $warehouse);
    }

    public function applyOpname(Product $product, float $actual, Model $reference, User $user, ?Warehouse $warehouse = null): StockMovement
    {
        $stock = $this->resolveStock($product, $warehouse, lock: false);
        $delta = round($actual - (float) $stock->quantity, 3);

        return $this->move($product, $delta, StockMovementType::Opname, $user, 'Hasil stock opname', $reference, null, $warehouse);
    }

    public function move(
        Product $product,
        float $delta,
        StockMovementType $type,
        User $user,
        ?string $notes = null,
        ?Model $reference = null,
        ?ProductVariant $variant = null,
        ?Warehouse $warehouse = null,
        bool $createMissing = false,
    ): StockMovement {
        return DB::transaction(function () use ($product, $delta, $type, $user, $notes, $reference, $variant, $warehouse, $createMissing) {
            $stock = $this->resolveStock($product, $warehouse, lock: true, createMissing: $createMissing);

            $before = (float) $stock->quantity;
            $after = round($before + $delta, 3);

            $settings = StoreSetting::query()->first();
            $allowNegative = $settings?->allow_negative_stock ?? false;

            if ($after < 0 && ! $allowNegative) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$product->name} tidak mencukupi.",
                ]);
            }

            $stock->update(['quantity' => $after]);

            if ($variant !== null) {
                $lockedVariant = ProductVariant::query()
                    ->whereKey($variant->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $variantAfter = round((float) $lockedVariant->quantity + $delta, 3);

                if ($variantAfter < 0 && ! $allowNegative) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok varian {$lockedVariant->name} tidak mencukupi.",
                    ]);
                }

                $lockedVariant->update(['quantity' => $variantAfter]);
            }

            $movement = StockMovement::query()->create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $delta,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_type' => $reference !== null ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $user->id,
                'notes' => $notes,
            ]);

            if ($after <= (float) $stock->minimum_stock) {
                $recipients = User::query()
                    ->whereIn('role', ['owner', 'administrator', 'manager'])
                    ->where('is_active', true)
                    ->get();

                Notification::send($recipients, new StockAlertNotification($product, $after));
            }

            return $movement;
        });
    }

    public function resolveStock(Product $product, ?Warehouse $warehouse = null, bool $lock = false, bool $createMissing = false): Stock
    {
        $warehouse ??= $this->locationContext->warehouse();

        $query = Stock::query()->where('product_id', $product->id);

        if ($warehouse !== null) {
            $query->where('warehouse_id', $warehouse->id);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        $stock = $query->first();

        if ($stock === null && $warehouse !== null && $createMissing) {
            return Stock::query()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 0,
                'minimum_stock' => 0,
            ]);
        }

        if ($stock === null) {
            $fallback = Stock::query()->where('product_id', $product->id);
            if ($lock) {
                $fallback->lockForUpdate();
            }
            $stock = $fallback->firstOrFail();
        }

        return $stock;
    }
}
