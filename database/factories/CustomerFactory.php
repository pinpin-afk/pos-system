<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::query()->value('id'),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'is_walk_in' => false,
        ];
    }

    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Walk-in Customer',
            'phone' => null,
            'email' => null,
            'address' => null,
            'is_walk_in' => true,
        ]);
    }
}
