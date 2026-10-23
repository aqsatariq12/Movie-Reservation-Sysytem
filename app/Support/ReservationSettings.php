<?php

namespace App\Support;

use App\Models\Setting;

class ReservationSettings
{
    /** Setting key => default, matching the defaults used across the app. */
    public const DEFAULTS = [
        'hold_minutes' => 10,
        'max_seats_per_booking' => 6,
        'cancellation_window_hours' => 2,
        'premium_surcharge_cents' => 200,
    ];

    /** @return array<string, int> */
    public static function current(): array
    {
        return collect(self::DEFAULTS)
            ->map(fn (int $default, string $key) => Setting::readInt($key, $default))
            ->all();
    }
}