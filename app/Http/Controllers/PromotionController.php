<?php

namespace App\Http\Controllers;

use App\Enums\PromotionType;
use App\Http\Requests\StorePromotionRequest;
use App\Models\Product;
use App\Models\Promotion;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        return Inertia::render('Promotions/Index', [
            'promotions' => Promotion::query()->with('product:id,name')->latest()->paginate(15),
            'products' => Product::query()
                ->active()
                ->atWarehouse($location->warehouse($request->user())?->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => collect(PromotionType::cases())->map(fn (PromotionType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'creating' => $request->boolean('create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('promotions.index', ['create' => 1]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::query()->create($request->validated());

        return redirect()->route('promotions.index')->with('success', 'Promo ditambahkan.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('promotions.index')->with('success', 'Promo dihapus.');
    }
}
