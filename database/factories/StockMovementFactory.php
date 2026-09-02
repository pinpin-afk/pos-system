<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => StockMovementType::Adjustment,
            'quantity' => 1,
            'stock_before' => 10,
            'stock_after' => 11,
            'user_id' => User::factory(),
            'notes' => 'Penyesuaian',
        ];
    }
}
