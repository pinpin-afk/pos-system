<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteStockOpnameRequest;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $warehouseId = $location->warehouse($request->user())?->id;

        return Inertia::render('StockOpnames/Index', [
            'opnames' => StockOpname::query()
                ->with('user:id,name')
                ->latest()
                ->paginate(15),
            'products' => Product::query()
                ->atWarehouse($warehouseId)
                ->with([
                    'stock' => fn ($query) => $warehouseId
                        ? $query->where('warehouse_id', $warehouseId)
                        : $query,
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'creating' => $request->boolean('create'),
            'viewingOpname' => $request->filled('opname')
                ? StockOpname::query()->with(['items.product', 'user:id,name'])->find($request->integer('opname'))
                : null,
        ]);
    }

    public function store(StoreStockOpnameRequest $request, StockOpnameService $stockOpnameService): RedirectResponse
    {
        $opname = $stockOpnameService->create($request->user(), $request->validated());

        return redirect()->route('stock-opnames.index', ['opname' => $opname->id])
            ->with('success', 'Draft stock opname disimpan.');
    }

    public function complete(CompleteStockOpnameRequest $request, StockOpname $stockOpname, StockOpnameService $stockOpnameService): RedirectResponse
    {
        $stockOpnameService->complete($stockOpname, $request->user());

        return redirect()->route('stock-opnames.index', ['opname' => $stockOpname->id])
            ->with('success', 'Stock opname diterapkan.');
    }
}
