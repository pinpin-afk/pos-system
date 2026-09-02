<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->city().' Store',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
