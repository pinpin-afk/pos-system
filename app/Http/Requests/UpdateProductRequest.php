<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ProductsManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product)],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'exists:product_variants,id'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:80'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:50'],
            'variants.*.barcode' => ['nullable', 'string', 'max:50'],
            'variants.*.purchase_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.selling_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->barcode === '') {
            $this->merge(['barcode' => null]);
        }
    }
}
