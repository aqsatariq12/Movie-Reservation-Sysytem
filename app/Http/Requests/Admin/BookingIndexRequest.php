<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingIndexRequest extends FormRequest
{
    public const TABS = ['all', 'pending', 'confirmed', 'cancelled'];

    public function authorize(): bool
    {
        // The "admin" middleware on the route group already protects this page.
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(self::TABS)],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($this->filled('from'), ['after_or_equal:from'])],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** Active status tab, defaults to "all". */
    public function tab(): string
    {
        return $this->validated('tab') ?: 'all';
    }
}