<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateReservationSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReservationSettingsRequest;
use App\Models\Setting;
use App\Support\ReservationSettings;

class SettingsController extends Controller
{
    public function edit()
    {
        $current = ReservationSettings::current();

        return view('admin.settings.edit', [
            'values' => [
                'hold_minutes'        => $current['hold_minutes'],
                'max_seats'           => $current['max_seats_per_booking'],
                'cancel_window_hours' => $current['cancellation_window_hours'],
                'premium_surcharge'   => number_format($current['premium_surcharge_cents'] / 100, 2, '.', ''),
            ],
            'salesCutoff' => Setting::readInt('sales_cutoff_minutes', 15),
        ]);
    }

    public function update(UpdateReservationSettingsRequest $request, UpdateReservationSettingsAction $update)
    {
        $changed = $update->execute($request->settingValues(), $request->user(), $request->ip());

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', $changed === []
                ? 'No changes to save.'
                : 'Settings saved. They apply to new holds and bookings only.');
    }
}