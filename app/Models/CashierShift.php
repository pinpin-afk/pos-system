<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Models\Concerns\ScopedToBranch;
use Database\Factories\CashierShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'branch_id',
    'opening_cash',
    'expected_cash',
    'actual_cash',
    'difference',
    'opened_at',
    'closed_at',
    'status',
])]
class CashierShift extends Model
{
    /** @use HasFactory<CashierShiftFactory> */
    use HasFactory;

    use ScopedToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => ShiftStatus::class,
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
     * @return HasMany<CashMovement, $this>
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', ShiftStatus::Open);
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }
}
