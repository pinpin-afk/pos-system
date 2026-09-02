<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Branches/Index', [
            'branches' => Branch::query()->withCount('warehouses')->latest()->paginate(15),
            'creating' => $request->boolean('create'),
            'editingBranch' => $request->filled('edit')
                ? Branch::query()->find($request->integer('edit'))
                : null,
            'plan' => Tenant::query()->first()?->plan?->label(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('branches.index', ['create' => 1]);
    }

    public function store(StoreBranchRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $tenant = Tenant::query()->firstOrFail();

        if (! $tenant->canAddBranch()) {
            throw ValidationException::withMessages([
                'name' => 'Paket tenant sudah mencapai batas cabang.',
            ]);
        }

        $branch = Branch::query()->create([
            ...$request->validated(),
            'tenant_id' => $tenant->id,
        ]);

        $branch->warehouses()->create([
            'name' => 'Gudang Utama',
            'is_default' => true,
            'is_active' => true,
        ]);

        Customer::ensureWalkIn($branch->id);

        $logger->log($request->user(), 'branch.created', $branch);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Branch $branch): RedirectResponse
    {
        return redirect()->route('branches.index', ['edit' => $branch->id]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->warehouses()->whereHas('stocks')->exists()) {
            return back()->with('error', 'Cabang masih punya stok di gudang.');
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Cabang dihapus.');
    }

    public function switch(Request $request, Branch $branch): RedirectResponse
    {
        $request->session()->put('current_branch_id', $branch->id);

        return back()->with('success', 'Cabang aktif: '.$branch->name);
    }
}
