<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\RefundSaleRequest;
use App\Http\Requests\VoidSaleRequest;
use App\Models\Sale;
use App\Services\RefundService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $branchId = $location->branch($request->user())?->id;

        $sales = Sale::query()
            ->forBranch($branchId)
            ->whereIn('status', [
                SaleStatus::Completed,
                SaleStatus::PartiallyRefunded,
                SaleStatus::Refunded,
                SaleStatus::Voided,
            ])
            ->with(['cashier:id,name', 'customer:id,name', 'payments', 'payment'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('invoice_number', 'like', "%{$search}%");
            })
            ->latest('completed_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => ['search' => $request->string('search')->toString()],
            'viewingSale' => $request->filled('sale')
                ? Sale::query()
                    ->forBranch($branchId)
                    ->with(['items', 'cashier:id,name', 'customer', 'payments', 'payment', 'refunds.items'])
                    ->find($request->integer('sale'))
                : null,
        ]);
    }

    public function show(Sale $sale, LocationContext $location): RedirectResponse
    {
        $this->ensureSaleBelongsToCurrentBranch($sale, $location->branch()?->id);

        return redirect()->route('sales.index', ['sale' => $sale->id]);
    }

    public function refund(RefundSaleRequest $request, Sale $sale, RefundService $refundService, LocationContext $location): RedirectResponse
    {
        $this->ensureSaleBelongsToCurrentBranch($sale, $location->branch($request->user())?->id);

        $refundService->refund($sale, $request->user(), $request->validated());

        return redirect()->route('sales.index', ['sale' => $sale->id])->with('success', 'Refund berhasil dicatat.');
    }

    public function void(VoidSaleRequest $request, Sale $sale, RefundService $refundService, LocationContext $location): RedirectResponse
    {
        $this->ensureSaleBelongsToCurrentBranch($sale, $location->branch($request->user())?->id);

        $refundService->void($sale, $request->user(), $request->validated('reason'));

        return redirect()->route('sales.index', ['sale' => $sale->id])->with('success', 'Transaksi di-void.');
    }

    private function ensureSaleBelongsToCurrentBranch(Sale $sale, ?int $branchId): void
    {
        abort_unless($sale->branch_id !== null && (int) $sale->branch_id === (int) $branchId, 404);
    }
}
