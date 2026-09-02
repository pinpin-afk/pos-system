<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'TRF-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'status' => TransferStatus::Pending,
        ];
    }
}
