<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::query()->latest()->paginate(15),
            'creating' => $request->boolean('create'),
            'editingSupplier' => $request->filled('edit')
                ? Supplier::query()->find($request->integer('edit'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['create' => 1]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): RedirectResponse
    {
        return redirect()->route('suppliers.index', ['edit' => $supplier->id]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Supplier sudah punya pembelian.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier dihapus.');
    }
}
