<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: replace with the owner's admin role check once merged
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', 'unique:screens,name'],
            'total_rows'    => ['required', 'integer', 'min:1', 'max:26'],
            'seats_per_row' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }
}