<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\Stock;
use App\Support\LocationContext;

class InsightService
{
    public function __construct(private LocationContext $location) {}

    /**
     * @return list<array{title: string, detail: string, tone: string}>
     */
    public function suggestions(): array
    {
        $suggestions = [];
        $branchId = $this->location->branch()?->id;
        $warehouseId = $this->location->warehouse()?->id;

        $low = Stock::query()
            ->with('product:id,name,sku')
            ->forWarehouse($warehouseId)
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->orderBy('quantity')
            ->limit(5)
            ->get();

        foreach ($low as $stock) {
            $suggestions[] = [
                'title' => 'Restock '.$stock->product?->name,
                'detail' => 'Stok '.$stock->product?->sku.' sisa '.$stock->quantity.'. Isi ulang sebelum habis.',
                'tone' => 'amber',
            ];
        }

        $soldIds = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->where('completed_at', '>=', now()->subDays(14))
            ->with('items')
            ->get()
            ->flatMap->items
            ->pluck('product_id');

        $dead = Stock::query()
            ->with('product:id,name,sku')
            ->forWarehouse($warehouseId)
            ->whereNotIn('product_id', $soldIds)
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit(3)
            ->get();

        foreach ($dead as $stock) {
            $suggestions[] = [
                'title' => 'Promo untuk '.$stock->product?->name,
                'detail' => 'Tidak laku 14 hari terakhir. Pertimbangkan diskon atau bundling.',
                'tone' => 'sky',
            ];
        }

        $expense = (float) Expense::query()
            ->forBranch($branchId)
            ->where('spent_on', '>=', now()->startOfMonth())
            ->sum('amount');
        $revenue = (float) Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->where('completed_at', '>=', now()->startOfMonth())
            ->sum('grand_total');

        if ($revenue > 0 && $expense / $revenue > 0.3) {
            $suggestions[] = [
                'title' => 'Pengeluaran bulan ini tinggi',
                'detail' => 'Expense mencapai '.round(($expense / $revenue) * 100).'% dari omzet. Tinjau biaya operasional.',
                'tone' => 'rose',
            ];
        }

        $top = Sale::query()
            ->completed()
            ->forBranch($branchId)
            ->whereDate('completed_at', now()->toDateString())
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->sortByDesc(fn ($items) => $items->sum('quantity'))
            ->take(1)
            ->first();

        if ($top?->first()) {
            $suggestions[] = [
                'title' => 'Produk terlaris hari ini: '.$top->first()->product_name,
                'detail' => 'Siapkan stok cadangan dan tampilkan di rak depan kasir.',
                'tone' => 'teal',
            ];
        }

        if ($suggestions === []) {
            $suggestions[] = [
                'title' => 'Operasi toko terlihat sehat',
                'detail' => 'Tidak ada alert stok atau anomali biaya saat ini.',
                'tone' => 'teal',
            ];
        }

        return $suggestions;
    }
}
