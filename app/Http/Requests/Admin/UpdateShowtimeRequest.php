<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class UpdateShowtimeRequest extends StoreShowtimeRequest
{
    public function rules(): array
    {
        // A booked show can only change its price: the other fields are locked and not even sent.
        if ($this->route('showtime')->hasActiveBookings()) {
            return ['base_price' => ['required', 'regex:/^\d{1,4}(\.\d{1,2})?$/']];
        }

        return parent::rules();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (!$this->route('showtime')->hasActiveBookings() && $this->startsAt()->isPast()) {
                    $validator->errors()->add('start_time', 'Start time must be in the future.');
                }

                if ($this->basePriceCents() < 1) {
                    $validator->errors()->add('base_price', 'Base price must be greater than zero.');
                }
            },
        ];
    }
}