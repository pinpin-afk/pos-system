<x-mail::message>
# Struk {{ $sale->invoice_number }}

**{{ $store->store_name }}**<br>
{{ $store->address }}<br>
{{ $store->phone }}

Pelanggan: {{ $sale->customer?->name }}<br>
Kasir: {{ $sale->cashier?->name }}

@foreach ($sale->items as $item)
- {{ $item->product_name }} × {{ $item->quantity }} — Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}
@endforeach

Subtotal: Rp{{ number_format((float) $sale->subtotal, 0, ',', '.') }}<br>
Diskon: Rp{{ number_format((float) $sale->discount_amount, 0, ',', '.') }}<br>
Pajak: Rp{{ number_format((float) $sale->tax, 0, ',', '.') }}<br>
**Total: Rp{{ number_format((float) $sale->grand_total, 0, ',', '.') }}**

{{ $store->receipt_footer }}
</x-mail::message>
