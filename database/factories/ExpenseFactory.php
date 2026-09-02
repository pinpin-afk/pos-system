<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::query()->value('id') ?? Branch::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(['Operasional', 'Sewa', 'Gaji', 'Utilitas', 'Lainnya']),
            'amount' => fake()->numberBetween(25000, 500000),
            'spent_on' => now()->toDateString(),
            'description' => fake()->sentence(),
        ];
    }
}
