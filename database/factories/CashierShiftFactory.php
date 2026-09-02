<?php

namespace Database\Factories;

use App\Enums\ShiftStatus;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierShift>
 */
class CashierShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::query()->value('id'),
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => ShiftStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_cash' => 500000,
            'actual_cash' => 500000,
            'difference' => 0,
            'closed_at' => now(),
            'status' => ShiftStatus::Closed,
        ]);
    }
}
