<?php

namespace Database\Factories;

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'SOP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'user_id' => User::factory()->owner(),
            'status' => StockOpnameStatus::Draft,
        ];
    }
}
