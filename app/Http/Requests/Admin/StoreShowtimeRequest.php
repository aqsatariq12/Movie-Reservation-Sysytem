<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: replace with the owner's admin role check once merged
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'movie_id'   => ['required', 'integer', Rule::exists('movies', 'id')->where(fn ($q) => $q->where('status', '!=', 'archived'))],
            'screen_id'  => ['required', 'integer', 'exists:screens,id'],
            'show_date'  => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'base_price' => ['required', 'regex:/^\d{1,4}(\.\d{1,2})?$/'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->startsAt()->isPast()) {
                    $validator->errors()->add('start_time', 'Start time must be in the future.');
                }

                if ($this->basePriceCents() < 1) {
                    $validator->errors()->add('base_price', 'Base price must be greater than zero.');
                }
            },
        ];
    }

    public function startsAt(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('show_date') . ' ' . $this->input('start_time'));
    }

    /** "10.50" => 1050 (string maths, no floats) */
    public function basePriceCents(): int
    {
        [$dollars, $cents] = array_pad(explode('.', (string) $this->input('base_price')), 2, '0');

        return ((int) $dollars * 100) + (int) str_pad($cents, 2, '0');
    }
}