<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->first() ?? Tenant::factory()->create([
            'name' => 'Toko Maju Jaya',
            'slug' => 'toko-maju-jaya',
            'plan' => 'unlimited',
        ]);

        $branch = Branch::query()->first() ?? Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cabang Pusat',
            'code' => 'PST',
        ]);

        $warehouse = Warehouse::query()->first() ?? Warehouse::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Gudang Utama',
            'is_default' => true,
        ]);

        Warehouse::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Gudang Cadangan'],
            ['is_default' => false, 'is_active' => true],
        );

        StoreSetting::query()->create([
            'tenant_id' => $tenant->id,
            'store_name' => 'Toko Maju Jaya',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'phone' => '02112345678',
            'email' => 'halo@tokomajujaya.test',
            'tax_rate' => 0,
            'tax_inclusive' => false,
            'invoice_prefix' => 'INV',
            'receipt_footer' => 'Terima kasih telah berbelanja di Toko Maju Jaya.',
            'allow_discount' => true,
            'allow_negative_stock' => false,
            'loyalty_enabled' => true,
            'loyalty_earn_points' => 1000,
            'loyalty_spend_amount' => 10000,
            'loyalty_redeem_points' => 1,
            'loyalty_redeem_amount' => 1,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
        ]);

        $owner = User::factory()->owner()->create([
            'name' => 'Owner',
            'email' => 'owner@pos.test',
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);

        User::factory()->cashier()->create([
            'name' => 'Kasir',
            'email' => 'cashier@pos.test',
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'pin' => '123456',
            'card_number' => 'EMP-001',
        ]);

        User::factory()->administrator()->create([
            'name' => 'Administrator',
            'email' => 'admin@pos.test',
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager',
            'email' => 'manager@pos.test',
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);

        User::factory()->supervisor()->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@pos.test',
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);

        Customer::ensureWalkIn($branch->id);

        $categories = collect([
            'Makanan' => [
                ['Indomie Goreng', 'SKU-NDL-001', '089686010667', 2800, 3500, 120, 20],
                ['Beras Ramos 5kg', 'SKU-BER-001', '8992761130011', 65000, 72000, 40, 8],
                ['Gula Pasir 1kg', 'SKU-GUL-001', '8992761100028', 14000, 16000, 60, 10],
            ],
            'Minuman' => [
                ['Aqua 600ml', 'SKU-AQU-001', '8886008101091', 3000, 4000, 150, 24],
                ['Teh Botol Sosro', 'SKU-TBS-001', '8991002101124', 5000, 7000, 80, 12],
                ['Coca Cola 330ml', 'SKU-COC-001', '8999999800011', 6000, 8000, 90, 12],
                ['Kopi Kapal Api', 'SKU-KOP-001', '8991002101551', 1500, 2000, 200, 30],
            ],
            'Snack' => [
                ['Chitato Sapi Panggang', 'SKU-CHI-001', '089686598032', 8000, 11000, 70, 10],
            ],
            'Kebutuhan' => [
                ['Minyak Goreng 1L', 'SKU-MYK-001', '8998866201012', 18000, 21000, 45, 8],
                ['Sabun Lifebuoy', 'SKU-SBN-001', '8999999032101', 4000, 5500, 80, 15],
            ],
        ]);

        $categories->each(function (array $products, string $categoryName) use ($owner, $warehouse): void {
            $category = Category::query()->create([
                'name' => $categoryName,
                'is_active' => true,
            ]);

            foreach ($products as [$name, $sku, $barcode, $purchase, $selling, $qty, $min]) {
                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'purchase_price' => $purchase,
                    'selling_price' => $selling,
                    'unit' => 'PCS',
                    'is_active' => true,
                ]);

                Stock::query()->create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $qty,
                    'minimum_stock' => $min,
                ]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::Initial,
                    'quantity' => $qty,
                    'stock_before' => 0,
                    'stock_after' => $qty,
                    'reference_type' => $product->getMorphClass(),
                    'reference_id' => $product->id,
                    'user_id' => $owner->id,
                    'notes' => 'Stok awal',
                ]);
            }
        });
    }
}
