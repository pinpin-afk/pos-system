<?php

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Promo '.fake()->word(),
            'type' => PromotionType::Percent,
            'value' => 10,
            'is_active' => true,
        ];
    }
}
