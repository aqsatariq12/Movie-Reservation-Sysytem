<?php

namespace App\Actions\Screens;

use App\Models\Screen;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncDisabledSeatsAction
{
    /**
     * Make the screen's disabled seats match the given list, all or nothing.
     *
     * @param  array<int, string>  $disabled  e.g. ['C2', 'C3', 'F12']
     */
    public function execute(Screen $screen, array $disabled): void
    {
        DB::transaction(function () use ($screen, $disabled) {
            $seats = $screen->seats()->lockForUpdate()->get();
            $reserved = $screen->reservedSeats();

            $toDisable = [];
            $toEnable = [];
            $blocked = [];

            foreach ($seats as $seat) {
                $key = $seat->row_label . $seat->seat_number;
                $wantDisabled = in_array($key, $disabled, true);

                if ($wantDisabled === $seat->is_disabled) {
                    continue;
                }

                if ($wantDisabled) {
                    if ($reserved->has($seat->id)) {
                        $blocked[] = $key;
                        continue;
                    }
                    $toDisable[] = $seat->id;
                } else {
                    $toEnable[] = $seat->id;
                }
            }

            if ($blocked) {
                throw ValidationException::withMessages([
                    'seat_title' => implode(', ', $blocked) . ' can’t be disabled',
                    'seat_body' => implode(', ', $blocked) . ' has a future booking or an active hold. No changes were saved. Seats with active holds or future bookings are protected.',
                ]);
            }

            if ($toDisable) {
                $screen->seats()->whereIn('id', $toDisable)->update(['is_disabled' => true]);
            }

            if ($toEnable) {
                $screen->seats()->whereIn('id', $toEnable)->update(['is_disabled' => false]);
            }
        });
    }
}