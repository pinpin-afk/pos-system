<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(),
            'editingCategory' => $request->filled('edit')
                ? Category::query()->find($request->integer('edit'))
                : null,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('categories.index');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): RedirectResponse
    {
        return redirect()->route('categories.index', ['edit' => $category->id]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori masih memiliki produk.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori dihapus.');
    }
}
