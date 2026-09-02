<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $search = $request->string('search')->toString();
        $warehouseId = $location->warehouse($request->user())?->id;
        $stockForWarehouse = fn ($query) => $warehouseId
            ? $query->where('warehouse_id', $warehouseId)
            : $query;

        $products = Product::query()
            ->atWarehouse($warehouseId)
            ->with(['category', 'brand', 'stock' => $stockForWarehouse, 'variants'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search],
            'creating' => $request->boolean('create'),
            'editingProduct' => $request->filled('edit')
                ? Product::query()
                    ->atWarehouse($warehouseId)
                    ->with(['stock' => $stockForWarehouse, 'variants'])
                    ->find($request->integer('edit'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('products.index', ['create' => 1]);
    }

    public function store(StoreProductRequest $request, StockService $stockService, LocationContext $location): RedirectResponse
    {
        $data = $request->safe()->except(['initial_stock', 'minimum_stock', 'image', 'variants']);

        DB::transaction(function () use ($data, $request, $stockService, $location): void {
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            $product = Product::query()->create($data);
            $product->stock()->create([
                'warehouse_id' => $location->warehouse($request->user())?->id,
                'quantity' => 0,
                'minimum_stock' => $request->validated('minimum_stock'),
            ]);

            $this->syncVariants($product, $request->validated('variants') ?? []);

            $initial = (float) $request->validated('initial_stock');

            if ($initial > 0) {
                $stockService->recordInitial($product, $initial, $request->user());
            }
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product, LocationContext $location): RedirectResponse
    {
        $this->ensureProductIsAtCurrentWarehouse($product, $location->warehouse()?->id);

        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    public function update(UpdateProductRequest $request, Product $product, LocationContext $location): RedirectResponse
    {
        $warehouseId = $location->warehouse($request->user())?->id;
        $this->ensureProductIsAtCurrentWarehouse($product, $warehouseId);

        $data = $request->safe()->except(['minimum_stock', 'image', 'variants']);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        $product->stock()
            ->when($warehouseId !== null, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->update([
                'minimum_stock' => $request->validated('minimum_stock'),
            ]);
        $this->syncVariants($product, $request->validated('variants') ?? []);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product, LocationContext $location): RedirectResponse
    {
        $this->ensureProductIsAtCurrentWarehouse($product, $location->warehouse($request->user())?->id);

        if ($product->stockMovements()->where('type', StockMovementType::Sale)->exists()) {
            return back()->with('error', 'Produk sudah pernah terjual dan tidak bisa dihapus.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $kept = [];

        foreach ($variants as $row) {
            $payload = [
                'name' => $row['name'],
                'sku' => $row['sku'],
                'barcode' => $row['barcode'] ?: null,
                'purchase_price' => $row['purchase_price'],
                'selling_price' => $row['selling_price'],
                'wholesale_price' => $row['wholesale_price'] ?? null,
                'quantity' => $row['quantity'] ?? 0,
                'is_active' => $row['is_active'] ?? true,
            ];

            $variant = isset($row['id'])
                ? ProductVariant::query()->where('product_id', $product->id)->find($row['id'])
                : null;

            $variant = $variant
                ? tap($variant)->update($payload)
                : $product->variants()->create($payload);

            $kept[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $kept ?: [0])->delete();
    }

    private function ensureProductIsAtCurrentWarehouse(Product $product, ?int $warehouseId): void
    {
        abort_unless(
            $warehouseId !== null
            && $product->stocks()->where('warehouse_id', $warehouseId)->exists(),
            404,
        );
    }
}
