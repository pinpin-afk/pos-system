<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $branchId = $location->branch($request->user())?->id;

        return Inertia::render('Customers/Index', [
            'customers' => Customer::query()
                ->forBranch($branchId)
                ->latest()
                ->paginate(15),
            'creating' => $request->boolean('create'),
            'editingCustomer' => $request->filled('edit')
                ? Customer::query()
                    ->forBranch($branchId)
                    ->find($request->integer('edit'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('customers.index', ['create' => 1]);
    }

    public function store(StoreCustomerRequest $request, LocationContext $location): RedirectResponse
    {
        $data = $request->validated();
        $data['member_number'] = $data['member_number'] ?: $this->nextMemberNumber();
        $data['branch_id'] = $location->branch($request->user())?->id;

        Customer::query()->create($data);

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer): RedirectResponse
    {
        return redirect()->route('customers.index', ['edit' => $customer->id]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, LocationContext $location): RedirectResponse
    {
        $this->ensureCustomerBelongsToCurrentBranch($customer, $location->branch($request->user())?->id);

        $data = $request->validated();
        $data['member_number'] = $data['member_number'] ?: $customer->member_number ?: $this->nextMemberNumber();

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Customer $customer, LocationContext $location): RedirectResponse
    {
        $this->ensureCustomerBelongsToCurrentBranch($customer, $location->branch($request->user())?->id);

        if ($customer->is_walk_in) {
            return back()->with('error', 'Pelanggan walk-in tidak bisa dihapus.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
    }

    private function nextMemberNumber(): string
    {
        $next = ((int) Customer::query()->max('id')) + 1;

        return 'MBR-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function ensureCustomerBelongsToCurrentBranch(Customer $customer, ?int $branchId): void
    {
        abort_unless($customer->branch_id !== null && (int) $customer->branch_id === (int) $branchId, 404);
    }
}
