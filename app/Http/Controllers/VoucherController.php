<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Http\Requests\StoreVoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Vouchers/Index', [
            'vouchers' => Voucher::query()->latest()->paginate(15),
            'discountTypes' => collect(DiscountType::cases())->map(fn (DiscountType $type) => [
                'value' => $type->value,
                'label' => $type === DiscountType::Percent ? 'Persen' : 'Nominal',
            ]),
            'creating' => $request->boolean('create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('vouchers.index', ['create' => 1]);
    }

    public function store(StoreVoucherRequest $request): RedirectResponse
    {
        Voucher::query()->create($request->validated());

        return redirect()->route('vouchers.index')->with('success', 'Voucher ditambahkan.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return redirect()->route('vouchers.index')->with('success', 'Voucher dihapus.');
    }
}
