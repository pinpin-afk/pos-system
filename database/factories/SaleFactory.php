<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => null,
            'cashier_id' => User::factory(),
            'customer_id' => Customer::factory()->walkIn(),
            'cashier_shift_id' => CashierShift::factory(),
            'branch_id' => Branch::query()->value('id'),
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax' => 0,
            'tax_rate' => 0,
            'grand_total' => 0,
            'cost_total' => 0,
            'profit' => 0,
            'status' => SaleStatus::Held,
            'held_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'status' => SaleStatus::Completed,
            'held_at' => null,
            'completed_at' => now(),
            'subtotal' => 8000,
            'grand_total' => 8000,
            'cost_total' => 5000,
            'profit' => 3000,
        ]);
    }
}
