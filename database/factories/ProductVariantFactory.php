<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['S', 'M', 'L', 'Merah', 'Biru']),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####??')),
            'barcode' => fake()->unique()->numerify('898############'),
            'purchase_price' => 5000,
            'selling_price' => 8000,
            'wholesale_price' => 7000,
            'quantity' => 10,
            'is_active' => true,
        ];
    }
}
