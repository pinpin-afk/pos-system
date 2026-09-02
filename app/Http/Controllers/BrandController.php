<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Brands/Index', [
            'brands' => Brand::query()->orderBy('name')->paginate(15),
            'creating' => $request->boolean('create'),
            'editingBrand' => $request->filled('edit')
                ? Brand::query()->find($request->integer('edit'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('brands.index', ['create' => 1]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::query()->create($request->validated());

        return redirect()->route('brands.index')->with('success', 'Merek berhasil ditambahkan.');
    }

    public function edit(Brand $brand): RedirectResponse
    {
        return redirect()->route('brands.index', ['edit' => $brand->id]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()->route('brands.index')->with('success', 'Merek berhasil diperbarui.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Merek masih dipakai produk.');
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Merek dihapus.');
    }
}
