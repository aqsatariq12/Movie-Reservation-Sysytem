<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the admin middleware already guards the route group
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string', 'max:100'],
            'verification' => ['nullable', 'in:all,verified,unverified'],
            'page'         => ['nullable', 'integer', 'min:1'],
        ];
    }
}