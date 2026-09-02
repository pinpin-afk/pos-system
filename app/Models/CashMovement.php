<?php

namespace App\Models;

use App\Enums\CashMovementType;
use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cashier_shift_id', 'type', 'amount', 'reason', 'user_id'])]
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<CashierShift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
