<?php

namespace Database\Factories;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'RFD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'sale_id' => Sale::factory()->completed(),
            'user_id' => User::factory()->owner(),
            'amount' => 8000,
            'reason' => 'Barang dikembalikan',
        ];
    }
}
