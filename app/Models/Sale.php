<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\SaleStatus;
use App\Models\Concerns\ScopedToBranch;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'invoice_number',
    'cashier_id',
    'customer_id',
    'cashier_shift_id',
    'branch_id',
    'warehouse_id',
    'subtotal',
    'discount_type',
    'discount_value',
    'discount_amount',
    'tax',
    'tax_rate',
    'grand_total',
    'cost_total',
    'profit',
    'refunded_amount',
    'points_redeemed',
    'voucher_code',
    'status',
    'held_at',
    'completed_at',
    'notes',
    'voided_by',
    'void_reason',
    'voided_at',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    use ScopedToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'profit' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'points_redeemed' => 'integer',
            'status' => SaleStatus::class,
            'held_at' => 'datetime',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<CashierShift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Completed);
    }

    #[Scope]
    protected function held(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Held);
    }

    public function isHeld(): bool
    {
        return $this->status === SaleStatus::Held;
    }

    public function isCompleted(): bool
    {
        return $this->status === SaleStatus::Completed;
    }

    public function canRefund(): bool
    {
        return in_array($this->status, [SaleStatus::Completed, SaleStatus::PartiallyRefunded], true);
    }

    public function canVoid(): bool
    {
        return $this->status === SaleStatus::Completed && (float) $this->refunded_amount === 0.0;
    }
}
