<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\LocationContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabelController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        return Inertia::render('Labels/Index', [
            'products' => Product::query()
                ->active()
                ->atWarehouse($location->warehouse($request->user())?->id)
                ->with('variants')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'barcode', 'selling_price']),
            'selected' => $request->input('ids', []),
        ]);
    }
}
