<?php

namespace App\Services;

use App\Enums\StockOpnameStatus;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use App\Support\LocationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(
        private DocumentNumberService $documentNumberService,
        private StockService $stockService,
        private LocationContext $location,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $user, array $payload): StockOpname
    {
        return DB::transaction(function () use ($user, $payload) {
            $opname = StockOpname::query()->create([
                'number' => $this->documentNumberService->next('SOP', StockOpname::class),
                'user_id' => $user->id,
                'status' => StockOpnameStatus::Draft,
                'notes' => $payload['notes'] ?? null,
            ]);

            $warehouseId = $this->location->warehouse($user)?->id;

            foreach ($payload['items'] as $row) {
                $product = Product::query()
                    ->with([
                        'stock' => fn ($query) => $warehouseId
                            ? $query->where('warehouse_id', $warehouseId)
                            : $query,
                    ])
                    ->findOrFail($row['product_id']);
                $system = (float) ($product->stock?->quantity ?? 0);
                $actual = (float) $row['actual_quantity'];

                $opname->items()->create([
                    'product_id' => $product->id,
                    'system_quantity' => $system,
                    'actual_quantity' => $actual,
                    'difference' => round($actual - $system, 3),
                ]);
            }

            return $opname->load('items.product');
        });
    }

    public function complete(StockOpname $opname, User $user): StockOpname
    {
        return DB::transaction(function () use ($opname, $user) {
            $opname = StockOpname::query()->whereKey($opname->id)->lockForUpdate()->with('items.product.stock')->firstOrFail();

            if (! $opname->isDraft()) {
                throw ValidationException::withMessages([
                    'opname' => 'Stock opname ini sudah selesai.',
                ]);
            }

            foreach ($opname->items as $item) {
                $this->stockService->applyOpname(
                    $item->product,
                    (float) $item->actual_quantity,
                    $opname,
                    $user,
                );
            }

            $opname->update([
                'status' => StockOpnameStatus::Completed,
                'completed_at' => now(),
            ]);

            return $opname->fresh(['items.product']);
        });
    }
}
