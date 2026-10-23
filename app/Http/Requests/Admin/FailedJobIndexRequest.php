<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FailedJobIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin middleware guards the route group
    }

    public function rules(): array
    {
        return [
            'job_class' => ['nullable', 'string', 'max:191'],
            'range'     => ['nullable', 'in:24h,7d,30d,all'],
            'search'    => ['nullable', 'string', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}