<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory()->completed(),
            'method' => PaymentMethod::Cash,
            'amount' => 8000,
            'tendered' => 10000,
            'change_amount' => 2000,
            'paid_at' => now(),
        ];
    }
}
