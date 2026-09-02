<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case Transfer = 'transfer';
    case Card = 'card';
    case Ewallet = 'ewallet';
    case Other = 'other';
    case Points = 'points';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Qris => 'QRIS',
            self::Transfer => 'Transfer',
            self::Card => 'Kartu',
            self::Ewallet => 'E-Wallet',
            self::Other => 'Lainnya',
            self::Points => 'Poin',
        };
    }

    public function isCashierSelectable(): bool
    {
        return $this !== self::Points;
    }

    /**
     * @return list<self>
     */
    public static function cashierMethods(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $method): bool => $method->isCashierSelectable(),
        ));
    }
}
