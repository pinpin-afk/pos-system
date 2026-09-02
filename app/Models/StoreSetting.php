<?php

namespace App\Models;

use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name',
    'logo',
    'address',
    'phone',
    'email',
    'tax_rate',
    'tax_inclusive',
    'invoice_prefix',
    'receipt_footer',
    'allow_discount',
    'allow_negative_stock',
    'loyalty_enabled',
    'loyalty_earn_points',
    'loyalty_spend_amount',
    'loyalty_redeem_points',
    'loyalty_redeem_amount',
    'timezone',
    'currency',
    'tenant_id',
])]
class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'allow_discount' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'loyalty_enabled' => 'boolean',
            'loyalty_earn_points' => 'integer',
            'loyalty_spend_amount' => 'decimal:2',
            'loyalty_redeem_points' => 'integer',
            'loyalty_redeem_amount' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    public function pointsEarnedFor(float $amount): int
    {
        if (! $this->loyalty_enabled) {
            return 0;
        }

        return $this->pointsForSpend($amount);
    }

    public function pointsForSpend(float $amount): int
    {
        $spend = (float) $this->loyalty_spend_amount;
        $points = (int) $this->loyalty_earn_points;

        if ($amount <= 0 || $spend <= 0 || $points <= 0) {
            return 0;
        }

        return (int) floor(($amount * $points) / $spend);
    }
}
