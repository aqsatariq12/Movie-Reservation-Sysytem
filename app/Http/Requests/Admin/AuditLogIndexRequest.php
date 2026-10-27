<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin middleware guards the route group
    }

    public function rules(): array
    {
        return [
            'actor'  => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'target' => ['nullable', 'string', 'max:100'],
            'date'   => ['nullable', 'date_format:Y-m-d'],
            'view'   => ['nullable', 'integer'],
            'page'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}