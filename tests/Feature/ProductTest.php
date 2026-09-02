<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_replace_product_image(): void
    {
        Storage::fake('public');
        $this->seedCore();
        $owner = User::factory()->owner()->create();
        $product = $this->productWithStock();
        $product->update(['image' => UploadedFile::fake()->image('lama.jpg')->store('products', 'public')]);
        $oldImage = $product->image;

        $this->actingAs($owner)->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'unit' => $product->unit,
            'minimum_stock' => $product->stock->minimum_stock,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertNotSame($oldImage, $product->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_pos_includes_product_image(): void
    {
        Storage::fake('public');
        $this->seedCore();
        $cashier = User::factory()->cashier()->create();
        CashierShift::factory()->create(['user_id' => $cashier->id]);
        $product = $this->productWithStock();
        $product->update(['image' => UploadedFile::fake()->image('kasir.jpg')->store('products', 'public')]);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->where(
                    'products',
                    fn (mixed $products): bool => collect($products)->contains('image', $product->image),
                )
            );
    }
}
