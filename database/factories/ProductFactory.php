<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->unique()->numerify('899############'),
            'purchase_price' => 5000,
            'selling_price' => 8000,
            'unit' => 'PCS',
            'is_active' => true,
        ];
    }
}
