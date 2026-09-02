<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::BrandsManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Brand $brand */
        $brand = $this->route('brand');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($brand)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
