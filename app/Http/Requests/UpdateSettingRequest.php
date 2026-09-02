<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::SettingsManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['sometimes', 'boolean'],
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'allow_discount' => ['sometimes', 'boolean'],
            'allow_negative_stock' => ['sometimes', 'boolean'],
            'loyalty_enabled' => ['required', 'boolean'],
            'loyalty_earn_points' => ['required', 'integer', 'min:1'],
            'loyalty_spend_amount' => ['required', 'numeric', 'min:1'],
            'loyalty_redeem_points' => ['sometimes', 'integer', 'min:1'],
            'loyalty_redeem_amount' => ['sometimes', 'numeric', 'min:1'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'loyalty_enabled' => $this->boolean('loyalty_enabled'),
            'loyalty_redeem_points' => $this->integer('loyalty_redeem_points') ?: 1,
            'loyalty_redeem_amount' => $this->input('loyalty_redeem_amount') ?: 1,
        ]);
    }
}
