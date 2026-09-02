<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_import_products_from_csv(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        Category::factory()->create(['name' => 'Snack']);

        $csv = "name,sku,barcode,category,purchase_price,selling_price,unit,stock,minimum_stock\nChitato,SKU-IMP-1,899111,Snack,8000,11000,PCS,20,5\n";

        $this->actingAs($owner)->post(route('imports.products'), [
            'file' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'SKU-IMP-1')->first();
        $this->assertNotNull($product);
        $this->assertEquals(20, (float) $product->stock()->value('quantity'));
    }

    public function test_owner_can_export_products_as_csv(): void
    {
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $this->productWithStock();

        $this->actingAs($owner)
            ->get(route('exports.products'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_cashier_cannot_import_or_export(): void
    {
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('exports.products'))->assertForbidden();
        $this->actingAs($cashier)->post(route('imports.products'), [
            'file' => UploadedFile::fake()->createWithContent('produk.csv', "name\nA\n"),
        ])->assertForbidden();
    }
}
