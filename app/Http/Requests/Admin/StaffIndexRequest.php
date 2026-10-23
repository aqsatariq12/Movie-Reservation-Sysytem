<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin middleware guards the route group
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'change' => ['nullable', 'integer'],
            'role'   => ['nullable', Rule::enum(Role::class)],
            'page'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}