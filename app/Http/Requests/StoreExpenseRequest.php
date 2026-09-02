<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ExpensesManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'spent_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
