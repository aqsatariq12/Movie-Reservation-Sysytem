<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the "admin" middleware already protects the route
    }

    public function rules(): array
    {
        return [
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'tab'      => ['nullable', 'in:day,movie'],
        ];
    }
}