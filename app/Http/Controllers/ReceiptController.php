<?php

namespace App\Http\Controllers;

use App\Mail\SaleReceiptMail;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function show(Sale $sale): Response
    {
        abort_unless($sale->isCompleted(), 404);

        $sale->load(['items', 'cashier:id,name', 'customer', 'payments']);

        return Inertia::render('Receipts/Show', [
            'sale' => $sale,
            'store' => StoreSetting::current(),
            'whatsappUrl' => $this->whatsappUrl($sale),
        ]);
    }

    public function email(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($sale->isCompleted(), 404);

        $sale->load(['items', 'cashier:id,name', 'customer', 'payments']);

        $email = $request->validate([
            'email' => ['nullable', 'email'],
        ])['email'] ?? $sale->customer?->email;

        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => 'Isi email pelanggan atau masukkan alamat email.',
            ]);
        }

        Mail::to($email)->send(new SaleReceiptMail($sale, StoreSetting::current()));

        return back()->with('success', 'Struk dikirim ke email.');
    }

    private function whatsappUrl(Sale $sale): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $sale->customer?->phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        $text = rawurlencode(
            'Struk '.$sale->invoice_number.' total Rp'.number_format((float) $sale->grand_total, 0, ',', '.')
        );

        return "https://wa.me/{$phone}?text={$text}";
    }
}
