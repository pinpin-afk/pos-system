<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'refund_id',
    'sale_item_id',
    'product_id',
    'product_variant_id',
    'quantity',
    'amount',
])]
class RefundItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Refund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * @return BelongsTo<SaleItem, $this>
     */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
