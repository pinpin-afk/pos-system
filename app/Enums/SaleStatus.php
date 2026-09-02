<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Held = 'held';
    case Completed = 'completed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Voided = 'voided';

    public function isSettled(): bool
    {
        return in_array($this, [
            self::Completed,
            self::PartiallyRefunded,
            self::Refunded,
            self::Voided,
        ], true);
    }
}
