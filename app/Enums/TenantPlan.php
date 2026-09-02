<?php

namespace App\Enums;

enum TenantPlan: string
{
    case Starter = 'starter';
    case Pro = 'pro';
    case Unlimited = 'unlimited';

    public function branchLimit(): int
    {
        return match ($this) {
            self::Starter => 1,
            self::Pro => 5,
            self::Unlimited => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter (1 cabang)',
            self::Pro => 'Pro (5 cabang)',
            self::Unlimited => 'Unlimited',
        };
    }
}
