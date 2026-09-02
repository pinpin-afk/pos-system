<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'PO-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory()->owner(),
            'status' => PurchaseStatus::Ordered,
            'subtotal' => 10000,
            'ordered_at' => now(),
        ];
    }
}
