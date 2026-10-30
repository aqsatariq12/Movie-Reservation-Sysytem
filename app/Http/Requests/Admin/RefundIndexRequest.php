<?php

namespace App\Http\Requests\Admin;

use App\Enums\RefundStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // The admin route middleware protects this request.
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(RefundStatus::class)],
            'from'   => ['nullable', 'date'],
            'to'     => [
                'nullable',
                'date',
                Rule::when($this->filled('from'), ['after_or_equal:from']),
            ],
        ];
    }
}
