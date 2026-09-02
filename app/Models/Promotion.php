<?php

namespace App\Models;

use App\Enums\PromotionType;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'type',
    'value',
    'buy_qty',
    'get_qty',
    'product_id',
    'starts_at',
    'ends_at',
    'is_active',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
