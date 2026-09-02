<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_opname_id',
    'product_id',
    'system_quantity',
    'actual_quantity',
    'difference',
])]
class StockOpnameItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:3',
            'actual_quantity' => 'decimal:3',
            'difference' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<StockOpname, $this>
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
