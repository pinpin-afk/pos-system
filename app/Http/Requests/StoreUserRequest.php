<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::UsersManage) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'pin' => ['nullable', 'digits_between:4,6'],
            'card_number' => ['nullable', 'string', 'max:50', 'unique:users,card_number'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
