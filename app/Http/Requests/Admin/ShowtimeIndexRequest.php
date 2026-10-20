<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShowtimeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
            'screen_id' => ['nullable', 'integer'],
            'movie_id'  => ['nullable', 'integer'],
            'tab'       => ['nullable', 'in:upcoming,past,cancelled'],
        ];
    }
}