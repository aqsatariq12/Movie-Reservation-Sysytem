<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin middleware guards the route group
    }

    public function rules(): array
    {
        return [
            'hold_minutes'        => ['required', 'integer', 'between:2,30'],
            'max_seats'           => ['required', 'integer', 'between:1,10'],
            'cancel_window_hours' => ['required', 'integer', 'between:0,72'],
            'premium_surcharge'   => ['required', 'numeric', 'decimal:0,2', 'between:0,50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hold_minutes'        => 'hold duration',
            'max_seats'           => 'maximum seats per booking',
            'cancel_window_hours' => 'cancellation window',
            'premium_surcharge'   => 'premium seat surcharge',
        ];
    }

    /** The form takes dollars; the app stores cents. */
    public function surchargeCents(): int
    {
        return (int) round(((float) $this->validated('premium_surcharge')) * 100);
    }

    /** Form fields mapped to the real setting keys. */
    public function settingValues(): array
    {
        return [
            'hold_minutes'              => (int) $this->validated('hold_minutes'),
            'max_seats_per_booking'     => (int) $this->validated('max_seats'),
            'cancellation_window_hours' => (int) $this->validated('cancel_window_hours'),
            'premium_surcharge_cents'   => $this->surchargeCents(),
        ];
    }
}