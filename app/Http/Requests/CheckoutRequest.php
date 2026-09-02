<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'held_sale_id' => ['nullable', 'exists:sales,id'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'redeem_points' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment' => ['required_without:payments', 'array'],
            'payment.method' => ['required_with:payment', Rule::enum(PaymentMethod::class)->except([PaymentMethod::Points])],
            'payment.amount' => ['required_with:payment', 'numeric', 'min:0'],
            'payment.tendered' => ['nullable', 'numeric', 'min:0'],
            'payment.reference_number' => ['nullable', 'string', 'max:100'],
            'payment.label' => ['nullable', 'string', 'max:50'],
            'payments' => ['required_without:payment', 'array', 'min:1'],
            'payments.*.method' => ['required', Rule::enum(PaymentMethod::class)->except([PaymentMethod::Points])],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
            'payments.*.label' => ['nullable', 'string', 'max:50'],
        ];
    }
}
