<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LocationContext $location): Response|RedirectResponse
    {
        if ($request->user()?->isCashier()) {
            return redirect()->route('pos.index');
        }

        $warehouseId = $location->warehouse($request->user())?->id;
        $branchId = $location->branch($request->user())?->id;

        $todaySales = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereDate('completed_at', now()->toDateString());

        $recentSales = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->with(['cashier:id,name', 'customer:id,name', 'payments', 'payment'])
            ->latest('completed_at')
            ->limit(8)
            ->get();

        $topProducts = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereDate('completed_at', now()->toDateString())
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(fn ($items) => [
                'name' => $items->first()->product_name,
                'quantity' => $items->sum('quantity'),
                'revenue' => $items->sum('subtotal'),
                'profit' => $items->sum('profit'),
            ])
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        $lowStock = Stock::query()
            ->with('product:id,name,sku')
            ->forWarehouse($warehouseId)
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->orderBy('quantity')
            ->limit(8)
            ->get();

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($branchId) {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date->toDateString(),
                'revenue' => (float) Sale::query()
                    ->completed()
                    ->forBranch($branchId)
                    ->whereDate('completed_at', $date)
                    ->sum('grand_total'),
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                'revenue_today' => (float) (clone $todaySales)->sum('grand_total'),
                'transactions_today' => (clone $todaySales)->count(),
                'profit_today' => (float) (clone $todaySales)->sum('profit'),
                'products' => Product::query()->atWarehouse($warehouseId)->count(),
                'customers' => Customer::query()
                    ->forBranch($branchId)
                    ->where('is_walk_in', false)
                    ->count(),
                'low_stock' => Stock::query()
                    ->forWarehouse($warehouseId)
                    ->whereColumn('quantity', '<=', 'minimum_stock')
                    ->count(),
            ],
            'chart' => $chart,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'lowStock' => $lowStock,
        ]);
    }
}
