<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Database\Factories\StockTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number',
    'from_warehouse_id',
    'to_warehouse_id',
    'user_id',
    'status',
    'notes',
    'received_at',
])]
class StockTransfer extends Model
{
    /** @use HasFactory<StockTransferFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<StockTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === TransferStatus::Pending;
    }
}
