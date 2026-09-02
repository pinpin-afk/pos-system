<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        private DocumentNumberService $documentNumberService,
        private StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $user, array $payload): StockTransfer
    {
        return DB::transaction(function () use ($user, $payload) {
            if ((int) $payload['from_warehouse_id'] === (int) $payload['to_warehouse_id']) {
                throw ValidationException::withMessages([
                    'to_warehouse_id' => 'Gudang tujuan harus berbeda.',
                ]);
            }

            $from = Warehouse::query()->findOrFail($payload['from_warehouse_id']);

            $transfer = StockTransfer::query()->create([
                'number' => $this->documentNumberService->next('TRF', StockTransfer::class),
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $payload['to_warehouse_id'],
                'user_id' => $user->id,
                'status' => TransferStatus::Pending,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $row) {
                $product = Product::query()->findOrFail($row['product_id']);
                $qty = (float) $row['quantity'];

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);

                $this->stockService->move(
                    $product,
                    -$qty,
                    StockMovementType::TransferOut,
                    $user,
                    'Transfer keluar '.$transfer->number,
                    $transfer,
                    warehouse: $from,
                );
            }

            return $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);
        });
    }

    public function receive(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->with('items.product')->firstOrFail();

            if (! $transfer->isPending()) {
                throw ValidationException::withMessages([
                    'transfer' => 'Transfer ini sudah diproses.',
                ]);
            }

            $to = $transfer->toWarehouse;

            foreach ($transfer->items as $item) {
                $this->stockService->move(
                    $item->product,
                    (float) $item->quantity,
                    StockMovementType::TransferIn,
                    $user,
                    'Transfer masuk '.$transfer->number,
                    $transfer,
                    warehouse: $to,
                    createMissing: true,
                );
            }

            $transfer->update([
                'status' => TransferStatus::Received,
                'received_at' => now(),
            ]);

            return $transfer->fresh(['fromWarehouse', 'toWarehouse', 'items.product']);
        });
    }
}
