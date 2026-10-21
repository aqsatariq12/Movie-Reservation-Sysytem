<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only a logged-in customer can hold seats (the route also has auth + verified middleware).
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'seat_ids' => ['required', 'array', 'min:1', 'max:20'],
            'seat_ids.*' => ['integer', 'distinct'],
        ];
    }

    /** @return array<int, int> */
    public function seatIds(): array
    {
        return array_map('intval', $this->input('seat_ids', []));
    }
}