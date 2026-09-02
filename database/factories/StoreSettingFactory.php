<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_name' => 'Toko Maju Jaya',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'phone' => '02112345678',
            'email' => 'toko@pos.test',
            'tax_rate' => 0,
            'tax_inclusive' => false,
            'invoice_prefix' => 'INV',
            'receipt_footer' => 'Terima kasih telah berbelanja.',
            'allow_discount' => true,
            'allow_negative_stock' => false,
            'loyalty_enabled' => true,
            'loyalty_earn_points' => 1000,
            'loyalty_spend_amount' => 10000,
            'loyalty_redeem_points' => 1,
            'loyalty_redeem_amount' => 1,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
        ];
    }
}
