<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\StoreSetting;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{amount: float, label: string|null}
     */
    public function autoDiscount(array $lines, float $subtotal, ?Customer $customer): array
    {
        $amount = 0.0;
        $label = null;
        $now = Carbon::now()->format('H:i:s');

        foreach (Promotion::query()->active()->get() as $promotion) {
            $extra = match ($promotion->type) {
                PromotionType::Percent => round($subtotal * ((float) $promotion->value / 100), 2),
                PromotionType::HappyHour => $this->happyHourAmount($promotion, $subtotal, $now),
                PromotionType::Member => $customer && ! $customer->is_walk_in
                    ? round($subtotal * ((float) $promotion->value / 100), 2)
                    : 0,
                PromotionType::BuyXGetY => $this->buyXGetYAmount($promotion, $lines),
            };

            if ($extra > $amount) {
                $amount = $extra;
                $label = $promotion->name;
            }
        }

        return ['amount' => $amount, 'label' => $label];
    }

    public function redeemPoints(int $points, StoreSetting $settings): float
    {
        if ($points <= 0) {
            return 0;
        }

        if (! $settings->loyalty_enabled) {
            throw ValidationException::withMessages([
                'redeem_points' => 'Program poin sedang nonaktif.',
            ]);
        }

        return (float) $points;
    }

    public function voucherAmount(?string $code, float $subtotal): float
    {
        $voucher = $this->findUsableVoucher($code);

        if ($voucher === null) {
            return 0;
        }

        $amount = $voucher->discount_type === DiscountType::Percent
            ? round($subtotal * ((float) $voucher->discount_value / 100), 2)
            : (float) $voucher->discount_value;

        return min($amount, $subtotal);
    }

    public function redeemVoucher(?string $code): void
    {
        $voucher = $this->findUsableVoucher($code);

        $voucher?->increment('used_count');
    }

    private function findUsableVoucher(?string $code): ?Voucher
    {
        if (! filled($code)) {
            return null;
        }

        $voucher = Voucher::query()->where('code', strtoupper(trim($code)))->first();

        if ($voucher === null || ! $voucher->isUsable()) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Voucher tidak valid atau sudah habis.',
            ]);
        }

        return $voucher;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function buyXGetYAmount(Promotion $promotion, array $lines): float
    {
        if ($promotion->product_id === null || ! $promotion->buy_qty || ! $promotion->get_qty) {
            return 0;
        }

        $line = collect($lines)->first(fn (array $row) => (int) ($row['attributes']['product_id'] ?? 0) === (int) $promotion->product_id);

        if ($line === null) {
            return 0;
        }

        $qty = (float) $line['quantity'];
        $sets = (int) floor($qty / ((int) $promotion->buy_qty + (int) $promotion->get_qty));
        $free = $sets * (int) $promotion->get_qty;

        return round($free * (float) $line['attributes']['selling_price'], 2);
    }

    private function happyHourAmount(Promotion $promotion, float $subtotal, string $now): float
    {
        if (! $promotion->starts_at || ! $promotion->ends_at) {
            return 0;
        }

        if ($now < $promotion->starts_at || $now > $promotion->ends_at) {
            return 0;
        }

        return round($subtotal * ((float) $promotion->value / 100), 2);
    }
}
