<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        return Inertia::render('PurchaseReturns/Index', [
            'returns' => PurchaseReturn::query()
                ->with(['supplier:id,name', 'user:id,name'])
                ->latest()
                ->paginate(15),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->active()
                ->atWarehouse($location->warehouse($request->user())?->id)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'purchase_price']),
            'creating' => $request->boolean('create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('purchase-returns.index', ['create' => 1]);
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseService $purchaseService): RedirectResponse
    {
        $purchaseService->returnToSupplier($request->user(), $request->validated());

        return redirect()->route('purchase-returns.index')->with('success', 'Retur ke supplier dicatat.');
    }
}
