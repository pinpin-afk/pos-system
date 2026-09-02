<?php

namespace App\Models;

use App\Enums\StockOpnameStatus;
use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'user_id', 'status', 'notes', 'completed_at'])]
class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StockOpnameStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<StockOpnameItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === StockOpnameStatus::Draft;
    }
}
