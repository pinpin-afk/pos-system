<?php

namespace Database\Factories;

use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'RTN-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory()->owner(),
            'status' => 'completed',
            'subtotal' => 4000,
            'reason' => 'Barang rusak',
            'returned_at' => now(),
        ];
    }
}
