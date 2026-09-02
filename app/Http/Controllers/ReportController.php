<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Support\LocationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(Request $request, LocationContext $location): Response
    {
        $from = Carbon::parse($request->input('from', now()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        $branchId = $location->branch($request->user())?->id;
        $warehouseId = $location->warehouse($request->user())?->id;

        $salesQuery = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereBetween('completed_at', [$from, $to]);

        $sales = [
            'revenue' => (float) (clone $salesQuery)->sum('grand_total'),
            'cost' => (float) (clone $salesQuery)->sum('cost_total'),
            'profit' => (float) (clone $salesQuery)->sum('profit'),
            'discount' => (float) (clone $salesQuery)->sum('discount_amount'),
            'tax' => (float) (clone $salesQuery)->sum('tax'),
            'transactions' => (clone $salesQuery)->count(),
        ];

        $sales['average'] = $sales['transactions'] > 0
            ? round($sales['revenue'] / $sales['transactions'], 2)
            : 0;

        $productSales = SaleItem::query()
            ->select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('SUM(subtotal) as revenue')
            ->selectRaw('SUM(profit) as profit')
            ->whereHas('sale', function ($query) use ($from, $to, $branchId): void {
                $query->completed()->forBranch($branchId)->whereBetween('completed_at', [$from, $to]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('quantity')
            ->limit(15)
            ->get();

        $categorySales = SaleItem::query()
            ->selectRaw('categories.name as category')
            ->selectRaw('SUM(sale_items.quantity) as quantity')
            ->selectRaw('SUM(sale_items.subtotal) as revenue')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereHas('sale', function ($query) use ($from, $to, $branchId): void {
                $query->completed()->forBranch($branchId)->whereBetween('completed_at', [$from, $to]);
            })
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();

        $customerSales = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereBetween('completed_at', [$from, $to])
            ->with('customer:id,name,member_number')
            ->get()
            ->groupBy('customer_id')
            ->map(fn ($group) => [
                'name' => $group->first()->customer?->name,
                'member_number' => $group->first()->customer?->member_number,
                'transactions' => $group->count(),
                'revenue' => $group->sum('grand_total'),
            ])
            ->sortByDesc('revenue')
            ->values();

        $payments = Payment::query()
            ->select('method')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->whereHas('sale', function ($query) use ($from, $to, $branchId): void {
                $query->completed()->forBranch($branchId)->whereBetween('completed_at', [$from, $to]);
            })
            ->groupBy('method')
            ->get();

        $cashierSales = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereBetween('completed_at', [$from, $to])
            ->with('cashier:id,name')
            ->get()
            ->groupBy('cashier_id')
            ->map(fn ($group) => [
                'name' => $group->first()->cashier?->name,
                'transactions' => $group->count(),
                'revenue' => $group->sum('grand_total'),
                'profit' => $group->sum('profit'),
            ])
            ->values();

        $inventory = Stock::query()
            ->with('product:id,name,sku')
            ->forWarehouse($warehouseId)
            ->orderBy('quantity')
            ->get();

        $soldIds = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query
                ->completed()
                ->forBranch($branchId)
                ->where('completed_at', '>=', now()->subDays(30)))
            ->pluck('product_id');

        $slowMoving = Stock::query()
            ->with('product:id,name,sku')
            ->forWarehouse($warehouseId)
            ->whereNotIn('product_id', $soldIds)
            ->orderBy('quantity')
            ->limit(20)
            ->get();

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'sales' => $sales,
            'productSales' => $productSales,
            'categorySales' => $categorySales,
            'customerSales' => $customerSales,
            'payments' => $payments,
            'cashierSales' => $cashierSales,
            'inventory' => $inventory,
            'slowMoving' => $slowMoving,
            'customerCount' => Customer::query()->forBranch($branchId)->where('is_walk_in', false)->count(),
            'expenses' => [
                'total' => (float) Expense::query()
                    ->forBranch($branchId)
                    ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
                    ->sum('amount'),
                'rows' => Expense::query()
                    ->select('category')
                    ->selectRaw('SUM(amount) as total')
                    ->forBranch($branchId)
                    ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->get(),
            ],
        ]);
    }
}
