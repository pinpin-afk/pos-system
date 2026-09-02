<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $search = $request->string('search')->toString();
        $warehouseId = $location->warehouse($request->user())?->id;

        $stocks = Stock::query()
            ->with('product.category')
            ->forWarehouse($warehouseId)
            ->whereHas('product', function ($query) use ($search): void {
                $query->when($search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Stock/Index', [
            'stocks' => $stocks,
            'filters' => ['search' => $search],
        ]);
    }

    public function adjust(AdjustStockRequest $request, Product $product, StockService $stockService): RedirectResponse
    {
        $stockService->adjust(
            $product,
            (float) $request->validated('quantity'),
            $request->validated('notes'),
            $request->user(),
        );

        return back()->with('success', 'Stok berhasil disesuaikan.');
    }

    public function movements(): Response
    {
        $movements = StockMovement::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stock/Movements', [
            'movements' => $movements,
        ]);
    }
}
