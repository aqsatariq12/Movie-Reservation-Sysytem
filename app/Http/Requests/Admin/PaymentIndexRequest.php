<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the "admin" route middleware already protects this
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'tab'    => ['nullable', Rule::in(['all', 'succeeded', 'processing', 'failed'])],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', Rule::when($this->filled('from'), ['after_or_equal:from'])],
        ];
    }

    public function tab(): string
    {
        return $this->validated('tab') ?: 'all';
    }
}