<?php

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cashier_shift_id' => CashierShift::factory(),
            'type' => CashMovementType::Out,
            'amount' => 10000,
            'reason' => 'Keperluan operasional',
            'user_id' => User::factory(),
        ];
    }
}
