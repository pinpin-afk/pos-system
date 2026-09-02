<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Supervisor = 'supervisor';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Administrator => 'Administrator',
            self::Manager => 'Manager',
            self::Supervisor => 'Supervisor',
            self::Cashier => 'Kasir',
        };
    }

    public function canAccessAdmin(): bool
    {
        return $this !== self::Cashier;
    }
}
