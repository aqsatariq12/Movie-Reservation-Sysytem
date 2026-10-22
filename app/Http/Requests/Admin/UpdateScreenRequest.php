<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: replace with the owner's admin role check once merged
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('screens', 'name')->ignore($this->route('screen')),
            ],
        ];
    }
}