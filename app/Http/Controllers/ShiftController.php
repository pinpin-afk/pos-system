<?php

namespace App\Http\Controllers;

use App\Enums\CashMovementType;
use App\Http\Requests\CloseShiftRequest;
use App\Http\Requests\OpenShiftRequest;
use App\Http\Requests\StoreCashMovementRequest;
use App\Models\CashierShift;
use App\Services\ShiftService;
use App\Support\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(Request $request, ShiftService $shiftService, LocationContext $location): Response
    {
        $shifts = CashierShift::query()
            ->with('user:id,name')
            ->when(
                $request->user()?->isCashier(),
                fn ($query) => $query->whereBelongsTo($request->user()),
                fn ($query) => $query->forBranch($location->branch($request->user())?->id),
            )
            ->latest('opened_at')
            ->paginate(15);

        $viewing = $request->filled('shift')
            ? CashierShift::query()
                ->with(['user:id,name', 'cashMovements.user:id,name'])
                ->when(
                    $request->user()?->isCashier(),
                    fn ($query) => $query->whereBelongsTo($request->user()),
                    fn ($query) => $query->forBranch($location->branch($request->user())?->id),
                )
                ->find($request->integer('shift'))
            : null;

        return Inertia::render('Shifts/Index', [
            'shifts' => $shifts,
            'viewingShift' => $viewing,
            'expectedCash' => $viewing
                ? ($viewing->isOpen() ? $shiftService->expectedCash($viewing) : (float) $viewing->expected_cash)
                : null,
        ]);
    }

    public function show(CashierShift $shift): RedirectResponse
    {
        return redirect()->route('shifts.index', ['shift' => $shift->id]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->openShift()) {
            return redirect()->route('pos.index');
        }

        return Inertia::render('Shifts/Open');
    }

    public function store(OpenShiftRequest $request, ShiftService $shiftService): RedirectResponse
    {
        $shiftService->open($request->user(), (float) $request->validated('opening_cash'));

        return redirect()->route('pos.index')->with('success', 'Shift dibuka.');
    }

    public function closeForm(Request $request, ShiftService $shiftService): Response|RedirectResponse
    {
        $shift = $request->user()?->openShift();

        if ($shift === null) {
            return redirect()->route('shifts.open');
        }

        return Inertia::render('Shifts/Close', [
            'shift' => $shift,
            'expectedCash' => $shiftService->expectedCash($shift),
        ]);
    }

    public function close(CloseShiftRequest $request, ShiftService $shiftService): RedirectResponse
    {
        $shift = $request->user()?->openShift();

        abort_if($shift === null, 403);

        $shiftService->close($shift, (float) $request->validated('actual_cash'));

        $home = $request->user()->canAccessAdmin() ? route('shifts.index') : route('shifts.open');

        return redirect($home)->with('success', 'Shift ditutup.');
    }

    public function cashMovement(StoreCashMovementRequest $request, ShiftService $shiftService): RedirectResponse
    {
        $shift = $request->user()?->openShift();

        abort_if($shift === null, 403);

        $shiftService->addCashMovement(
            $shift,
            $request->user(),
            CashMovementType::from($request->validated('type')),
            (float) $request->validated('amount'),
            $request->validated('reason'),
        );

        return back()->with('success', 'Pergerakan kas tercatat.');
    }
}
