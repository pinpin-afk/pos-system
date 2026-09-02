<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'discount_type',
    'discount_value',
    'max_uses',
    'used_count',
    'expires_at',
    'is_active',
])]
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function usable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function remainingUses(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - (int) $this->used_count);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->remainingUses() === null || $this->remainingUses() > 0;
    }
}
