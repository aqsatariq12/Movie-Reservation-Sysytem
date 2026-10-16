<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'hold_minutes' => 10,
            'max_seats_per_booking' => 6,
            'sales_cutoff_minutes' => 15,
            'cancellation_window_hours' => 2,
            'premium_surcharge_cents' => 200, // premium seat = base price + $2.00
        ];

        foreach ($settings as $key => $value) {
            // firstOrCreate: re-seeding must not overwrite values an admin changed.
            Setting::firstOrCreate(['key' => $key], ['value' => (string) $value]);
        }
    }
}
