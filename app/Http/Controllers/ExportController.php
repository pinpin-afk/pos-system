<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CsvService;
use App\Support\LocationContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function products(CsvService $csvService, LocationContext $location): StreamedResponse
    {
        $warehouseId = $location->warehouse()?->id;

        $rows = Product::query()
            ->atWarehouse($warehouseId)
            ->with([
                'category',
                'brand',
                'stock' => fn ($query) => $warehouseId
                    ? $query->where('warehouse_id', $warehouseId)
                    : $query,
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                $product->name,
                $product->sku,
                $product->barcode,
                $product->category?->name,
                $product->brand?->name,
                $product->purchase_price,
                $product->selling_price,
                $product->wholesale_price,
                $product->stock?->quantity,
            ]);

        return $csvService->download('produk.csv', [
            'name', 'sku', 'barcode', 'category', 'brand', 'purchase_price', 'selling_price', 'wholesale_price', 'stock',
        ], $rows);
    }

    public function customers(CsvService $csvService, LocationContext $location): StreamedResponse
    {
        $rows = Customer::query()
            ->forBranch($location->branch()?->id)
            ->latest()
            ->get()
            ->map(fn (Customer $customer) => [
                $customer->name,
                $customer->phone,
                $customer->email,
                $customer->member_number,
                $customer->birthday?->toDateString(),
                $customer->points,
            ]);

        return $csvService->download('pelanggan.csv', [
            'name', 'phone', 'email', 'member_number', 'birthday', 'points',
        ], $rows);
    }

    public function sales(Request $request, CsvService $csvService, LocationContext $location): StreamedResponse
    {
        $rows = Sale::query()
            ->completed()
            ->forBranch($location->branch($request->user())?->id)
            ->with(['cashier:id,name', 'customer:id,name'])
            ->latest('completed_at')
            ->get()
            ->map(fn (Sale $sale) => [
                $sale->invoice_number,
                $sale->completed_at?->toDateTimeString(),
                $sale->cashier?->name,
                $sale->customer?->name,
                $sale->grand_total,
                $sale->tax,
                $sale->profit,
                $sale->status->value,
            ]);

        return $csvService->download('penjualan.csv', [
            'invoice', 'completed_at', 'cashier', 'customer', 'total', 'tax', 'profit', 'status',
        ], $rows);
    }

    public function printSales(Request $request, LocationContext $location): Response
    {
        $sales = Sale::query()
            ->completed()
            ->forBranch($location->branch($request->user())?->id)
            ->with(['cashier:id,name', 'customer:id,name'])
            ->latest('completed_at')
            ->limit(200)
            ->get();

        return Inertia::render('Exports/PrintSales', [
            'sales' => $sales,
        ]);
    }
}
