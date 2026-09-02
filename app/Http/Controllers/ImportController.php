<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCsvRequest;
use App\Services\CsvService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Imports/Index');
    }

    public function products(ImportCsvRequest $request, CsvService $csvService, StockService $stockService): RedirectResponse
    {
        $count = $csvService->importProducts($request->file('file'), $request->user(), $stockService);

        return back()->with('success', "{$count} produk diimpor.");
    }

    public function customers(ImportCsvRequest $request, CsvService $csvService): RedirectResponse
    {
        $count = $csvService->importCustomers($request->file('file'));

        return back()->with('success', "{$count} pelanggan diimpor.");
    }

    public function stock(ImportCsvRequest $request, CsvService $csvService): RedirectResponse
    {
        $count = $csvService->importStock($request->file('file'));

        return back()->with('success', "{$count} stok diimpor.");
    }
}
