<?php

namespace Database\Seeders;

use App\Enums\SeatType;
use App\Models\Screen;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class ScreenSeeder extends Seeder
{
    /**
     * Two screens with their physical seats.
     *
     * NOTE for the seat-map feature: this is simple demo data. The proper
     * "generate seats for a screen" admin Action replaces this later.
     * showtime_seats (per-showtime availability) are NOT created here; that is
     * part of the seat-holds feature.
     */
    public function run(): void
    {
        $screens = [
            [
                'name' => 'Screen 1',
                'rows' => 8,
                'per_row' => 12,
                'premium_rows' => 2,          // the last 2 rows (G, H)
                'disabled' => ['D6'],         // one out-of-service seat, handy for testing
            ],
            [
                'name' => 'Screen 2 IMAX',
                'rows' => 10,
                'per_row' => 14,
                'premium_rows' => 3,          // the last 3 rows (H, I, J)
                'disabled' => [],
            ],
        ];

        foreach ($screens as $config) {
            $screen = Screen::updateOrCreate(
                ['name' => $config['name']],
                ['total_rows' => $config['rows'], 'seats_per_row' => $config['per_row']],
            );

            for ($row = 0; $row < $config['rows']; $row++) {
                $label = chr(ord('A') + $row);
                $type = $row >= $config['rows'] - $config['premium_rows']
                    ? SeatType::Premium
                    : SeatType::Standard;

                for ($number = 1; $number <= $config['per_row']; $number++) {
                    Seat::firstOrCreate(
                        ['screen_id' => $screen->id, 'row_label' => $label, 'seat_number' => $number],
                        ['type' => $type, 'is_disabled' => in_array($label.$number, $config['disabled'], true)],
                    );
                }
            }
        }
    }
}
