<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route is already behind the 'admin' middleware.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirm' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Enter a cancellation reason.',
            'reason.min' => 'The reason must be at least 5 characters.',
            'confirm.accepted' => 'Tick the box to confirm you understand.',
        ];
    }
}