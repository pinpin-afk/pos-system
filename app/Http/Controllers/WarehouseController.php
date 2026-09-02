<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request, LocationContext $location): Response
    {
        $branchId = $location->branch($request->user())?->id;

        return Inertia::render('Warehouses/Index', [
            'warehouses' => Warehouse::query()
                ->with('branch:id,name')
                ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
                ->latest()
                ->paginate(15),
            'creating' => $request->boolean('create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('warehouses.index', ['create' => 1]);
    }

    public function store(StoreWarehouseRequest $request, LocationContext $location): RedirectResponse
    {
        DB::transaction(function () use ($request, $location): void {
            $data = $request->validated();
            $data['branch_id'] = $location->branch($request->user())?->id;

            if (! empty($data['is_default'])) {
                Warehouse::query()
                    ->where('branch_id', $data['branch_id'])
                    ->update(['is_default' => false]);
            }

            Warehouse::query()->create($data);
        });

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function destroy(Request $request, Warehouse $warehouse, LocationContext $location): RedirectResponse
    {
        abort_unless(
            $warehouse->branch_id !== null
            && (int) $warehouse->branch_id === (int) $location->branch($request->user())?->id,
            404,
        );

        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'Gudang masih berisi stok.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Gudang dihapus.');
    }
}
