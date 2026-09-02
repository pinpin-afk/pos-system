<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'category_id',
    'brand_id',
    'name',
    'sku',
    'barcode',
    'purchase_price',
    'selling_price',
    'wholesale_price',
    'unit',
    'image',
    'description',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasOne<Stock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function inStockAt(Builder $query, ?int $warehouseId): Builder
    {
        return $query->whereHas('stocks', function (Builder $stockQuery) use ($warehouseId): void {
            $stockQuery->where('quantity', '>', 0);

            if ($warehouseId !== null) {
                $stockQuery->where('warehouse_id', $warehouseId);
            }
        });
    }

    #[Scope]
    protected function atWarehouse(Builder $query, ?int $warehouseId): Builder
    {
        return $query->whereHas('stocks', function (Builder $stockQuery) use ($warehouseId): void {
            if ($warehouseId !== null) {
                $stockQuery->where('warehouse_id', $warehouseId);
            }
        });
    }

    public function isLowStock(): bool
    {
        if ($this->stock === null) {
            return false;
        }

        return (float) $this->stock->quantity <= (float) $this->stock->minimum_stock;
    }
}
