<?php

namespace App\Enums;

enum PromotionType: string
{
    case Percent = 'percent';
    case BuyXGetY = 'buy_x_get_y';
    case HappyHour = 'happy_hour';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Diskon persen',
            self::BuyXGetY => 'Beli X gratis Y',
            self::HappyHour => 'Happy hour',
            self::Member => 'Diskon member',
        };
    }
}
