<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivePurchaseRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        return Inertia::render('Purchases/Index', [
            'purchases' => Purchase::query()
                ->with(['supplier:id,name', 'user:id,name'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()
                ->active()
                ->atWarehouse($location->warehouse($request->user())?->id)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'purchase_price']),
            'creating' => $request->boolean('create'),
            'viewingPurchase' => $request->filled('purchase')
                ? Purchase::query()->with(['supplier', 'items.product', 'user:id,name'])->find($request->integer('purchase'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('purchases.index', ['create' => 1]);
    }

    public function store(StorePurchaseRequest $request, PurchaseService $purchaseService): RedirectResponse
    {
        $purchase = $purchaseService->create($request->user(), $request->validated());

        return redirect()->route('purchases.index', ['purchase' => $purchase->id])
            ->with('success', 'Pesanan pembelian dibuat.');
    }

    public function show(Purchase $purchase): RedirectResponse
    {
        return redirect()->route('purchases.index', ['purchase' => $purchase->id]);
    }

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase, PurchaseService $purchaseService): RedirectResponse
    {
        $purchaseService->receive($purchase, $request->user());

        return redirect()->route('purchases.index', ['purchase' => $purchase->id])
            ->with('success', 'Barang diterima dan stok bertambah.');
    }
}
