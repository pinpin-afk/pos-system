<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\StoreSetting;

class InvoiceNumberService
{
    public function next(): string
    {
        $settings = StoreSetting::current();
        $prefix = $settings->invoice_prefix ?: 'INV';
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $last = Sale::query()
            ->where('invoice_number', 'like', $pattern)
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $sequence);
    }
}
