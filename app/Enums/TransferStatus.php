<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Received => 'Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
