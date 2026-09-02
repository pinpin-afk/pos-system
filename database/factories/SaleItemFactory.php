<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_name' => 'Produk',
            'sku' => 'SKU-1',
            'quantity' => 1,
            'purchase_price' => 5000,
            'selling_price' => 8000,
            'discount_amount' => 0,
            'subtotal' => 8000,
            'profit' => 3000,
        ];
    }
}
