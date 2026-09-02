<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Enums\PromotionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::PromotionsManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(PromotionType::class)],
            'value' => ['nullable', 'numeric', 'min:0'],
            'buy_qty' => ['nullable', 'integer', 'min:1'],
            'get_qty' => ['nullable', 'integer', 'min:1'],
            'product_id' => ['nullable', 'exists:products,id'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
