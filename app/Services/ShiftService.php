<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\CashDifferenceNotification;
use App\Support\LocationContext;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function __construct(private LocationContext $locationContext) {}

    public function open(User $user, float $openingCash): CashierShift
    {
        if ($user->openShift() !== null) {
            throw ValidationException::withMessages([
                'opening_cash' => 'Masih ada shift yang terbuka.',
            ]);
        }

        return CashierShift::query()->create([
            'user_id' => $user->id,
            'branch_id' => $this->locationContext->branch($user)?->id,
            'opening_cash' => $openingCash,
            'opened_at' => now(),
            'status' => ShiftStatus::Open,
        ]);
    }

    public function expectedCash(CashierShift $shift): float
    {
        $cashSales = (float) Payment::query()
            ->where('method', PaymentMethod::Cash)
            ->whereHas('sale', function ($query) use ($shift): void {
                $query->where('cashier_shift_id', $shift->id)
                    ->where('status', SaleStatus::Completed);
            })
            ->sum('amount');

        $cashIn = (float) $shift->cashMovements()->where('type', CashMovementType::In)->sum('amount');
        $cashOut = (float) $shift->cashMovements()->where('type', CashMovementType::Out)->sum('amount');

        return round((float) $shift->opening_cash + $cashSales + $cashIn - $cashOut, 2);
    }

    public function addCashMovement(CashierShift $shift, User $user, CashMovementType $type, float $amount, string $reason): CashMovement
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'Shift sudah ditutup.',
            ]);
        }

        return $shift->cashMovements()->create([
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'user_id' => $user->id,
        ]);
    }

    public function close(CashierShift $shift, float $actualCash): CashierShift
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'actual_cash' => 'Shift sudah ditutup.',
            ]);
        }

        $expected = $this->expectedCash($shift);
        $difference = round($actualCash - $expected, 2);

        $shift->update([
            'expected_cash' => $expected,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'closed_at' => now(),
            'status' => ShiftStatus::Closed,
        ]);

        if (abs($difference) >= 0.01) {
            $recipients = User::query()
                ->whereIn('role', ['owner', 'administrator', 'manager'])
                ->where('is_active', true)
                ->get();

            Notification::send($recipients, new CashDifferenceNotification($shift->fresh()));
        }

        return $shift->refresh();
    }
}
