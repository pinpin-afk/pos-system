<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'name' => 'Voucher '.fake()->word(),
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 5000,
            'max_uses' => 10,
            'used_count' => 0,
            'is_active' => true,
        ];
    }
}
