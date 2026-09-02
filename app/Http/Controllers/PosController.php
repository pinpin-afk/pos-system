<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\HoldSaleRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Services\CheckoutService;
use App\Support\LocationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $heldId = $request->integer('held');

        $heldSale = null;

        if ($heldId > 0) {
            $heldSale = Sale::query()
                ->held()
                ->with('items')
                ->where('cashier_id', $user->id)
                ->findOrFail($heldId);
        }

        $location = app(LocationContext::class);
        $branchId = $location->branch($user)?->id;
        $warehouseId = $location->warehouse($user)?->id;

        $products = Product::query()
            ->active()
            ->inStockAt($warehouseId)
            ->with([
                'category:id,name',
                'stock' => fn ($query) => $warehouseId
                    ? $query->where('warehouse_id', $warehouseId)
                    : $query,
                'variants',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'selling_price' => (float) $product->selling_price,
                'wholesale_price' => (float) ($product->wholesale_price ?? 0),
                'unit' => $product->unit,
                'image' => $product->image,
                'category_id' => $product->category_id,
                'category' => $product->category?->name,
                'stock' => (float) ($product->stock?->quantity ?? 0),
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'selling_price' => (float) $variant->selling_price,
                    'quantity' => (float) $variant->quantity,
                ]),
            ]);

        $walkIn = Customer::ensureWalkIn($branchId);
        $customers = collect([$walkIn]);

        if ($heldSale?->customer_id) {
            $heldCustomer = Customer::query()
                ->forBranch($branchId)
                ->whereKey($heldSale->customer_id)
                ->first(['id', 'name', 'phone', 'email', 'is_walk_in', 'points']);

            if ($heldCustomer && $customers->doesntContain('id', $heldCustomer->id)) {
                $customers->push($heldCustomer);
            }
        }

        return Inertia::render('Pos/Index', [
            'products' => $products,
            'categories' => Category::query()->active()->orderBy('name')->get(['id', 'name']),
            'customers' => $customers
                ->map(fn (Customer $customer) => $this->customerPayload($customer))
                ->values(),
            'heldSales' => Sale::query()
                ->held()
                ->with('customer:id,name,phone,points,is_walk_in')
                ->where('cashier_id', $user->id)
                ->latest('held_at')
                ->get(),
            'heldSale' => $heldSale,
            'shift' => $user->openShift(),
            'settings' => StoreSetting::current()->only([
                'store_name',
                'allow_discount',
                'tax_rate',
                'tax_inclusive',
                'allow_negative_stock',
                'loyalty_enabled',
                'loyalty_earn_points',
                'loyalty_spend_amount',
                'loyalty_redeem_points',
                'loyalty_redeem_amount',
            ]),
            'paymentMethods' => collect(PaymentMethod::cashierMethods())->map(fn (PaymentMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ]),
        ]);
    }

    public function checkout(CheckoutRequest $request, CheckoutService $checkoutService): RedirectResponse
    {
        $shift = $request->user()->openShift();
        abort_if($shift === null, 403);

        $sale = $checkoutService->checkout($request->user(), $shift, $request->validated());

        return redirect()->route('receipts.show', $sale)->with('success', 'Transaksi berhasil.');
    }

    public function sync(Request $request, CheckoutService $checkoutService): RedirectResponse
    {
        $shift = $request->user()->openShift();
        abort_if($shift === null, 403);

        $payload = $request->validate([
            'checkouts' => ['required', 'array', 'min:1'],
            'checkouts.*' => ['required', 'array'],
        ]);

        $last = null;

        foreach ($payload['checkouts'] as $checkout) {
            $validated = validator($checkout, (new CheckoutRequest)->rules())->validate();
            $last = $checkoutService->checkout($request->user(), $shift, $validated);
        }

        return redirect()->route('receipts.show', $last)->with('success', 'Antrian offline berhasil disinkronkan.');
    }

    public function hold(HoldSaleRequest $request, CheckoutService $checkoutService): RedirectResponse
    {
        $shift = $request->user()->openShift();
        abort_if($shift === null, 403);

        $checkoutService->hold($request->user(), $shift, $request->validated());

        return redirect()->route('pos.index')->with('success', 'Transaksi ditahan.');
    }

    public function discardHold(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($sale->isHeld() && $sale->cashier_id === $request->user()->id, 403);

        $sale->items()->delete();
        $sale->delete();

        return redirect()->route('pos.index')->with('success', 'Transaksi hold dibatalkan.');
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = trim((string) ($request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ])['q'] ?? ''));

        $branchId = app(LocationContext::class)->branch($request->user())?->id;
        $walkIn = Customer::ensureWalkIn($branchId);

        $members = Customer::query()
            ->forBranch($branchId)
            ->where('is_walk_in', false)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'phone', 'email', 'is_walk_in', 'points']);

        $customers = collect();

        if ($walkIn) {
            $customers->push($walkIn);
        }

        return response()->json([
            'customers' => $customers
                ->concat($members)
                ->unique('id')
                ->values()
                ->map(fn (Customer $customer) => $this->customerPayload($customer)),
        ]);
    }

    public function storeCustomer(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create([
            ...$request->validated(),
            'branch_id' => app(LocationContext::class)->branch($request->user())?->id,
        ]);

        return back()->with('success', 'Pelanggan ditambahkan.')->with('created_customer', $this->customerPayload($customer));
    }

    /**
     * @return array{id: int, name: string, phone: ?string, email: ?string, is_walk_in: bool, points: int}
     */
    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'is_walk_in' => (bool) $customer->is_walk_in,
            'points' => (int) $customer->points,
        ];
    }
}
