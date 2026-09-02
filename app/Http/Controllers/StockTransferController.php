<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use App\Services\StockTransferService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $warehouseIds = $location->warehouseIds($request->user());

        return Inertia::render('Transfers/Index', [
            'transfers' => StockTransfer::query()
                ->with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'user:id,name', 'items.product:id,name,sku'])
                ->when($warehouseIds !== [], function ($query) use ($warehouseIds): void {
                    $query->where(function ($query) use ($warehouseIds): void {
                        $query->whereIn('from_warehouse_id', $warehouseIds)
                            ->orWhereIn('to_warehouse_id', $warehouseIds);
                    });
                })
                ->latest()
                ->paginate(15),
            'warehouses' => Warehouse::query()->where('is_active', true)->with('branch:id,name')->orderBy('name')->get(),
            'products' => Product::query()
                ->active()
                ->atWarehouse($location->warehouse($request->user())?->id)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'creating' => $request->boolean('create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('transfers.index', ['create' => 1]);
    }

    public function store(StoreStockTransferRequest $request, StockTransferService $service, ActivityLogger $logger): RedirectResponse
    {
        $transfer = $service->create($request->user(), $request->validated());
        $logger->log($request->user(), 'transfer.created', $transfer);

        return redirect()->route('transfers.index')->with('success', 'Transfer stok dicatat.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockTransferService $service, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('transfers.manage'), 403);

        $service->receive($transfer, $request->user());
        $logger->log($request->user(), 'transfer.received', $transfer);

        return back()->with('success', 'Transfer diterima.');
    }
}
