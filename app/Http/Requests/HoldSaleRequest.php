<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoldSaleRequest extends FormRequest
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
            'customer_id' => ['nullable', 'exists:customers,id'],
            'held_sale_id' => ['nullable', 'exists:sales,id'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
