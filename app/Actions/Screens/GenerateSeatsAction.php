<?php

namespace App\Actions\Screens;

use App\Models\Screen;
use Illuminate\Support\Facades\DB;

class GenerateSeatsAction
{
    /**
     * @param  array<int, string>  $premiumRows    e.g. ['G', 'H']
     * @param  array<int, string>  $disabledSeats  e.g. ['C2', 'C3', 'F12']
     */
    public function execute(Screen $screen, array $premiumRows = [], array $disabledSeats = []): int
    {
        return DB::transaction(function () use ($screen, $premiumRows, $disabledSeats) {
            $now = now();
            $rows = [];

            for ($r = 0; $r < $screen->total_rows; $r++) {
                $label = self::rowLabel($r);
                $type = in_array($label, $premiumRows, true) ? 'premium' : 'standard';

                for ($n = 1; $n <= $screen->seats_per_row; $n++) {
                    $rows[] = [
                        'screen_id' => $screen->id,
                        'row_label' => $label,
                        'seat_number' => $n,
                        'type' => $type,
                        'is_disabled' => in_array($label . $n, $disabledSeats, true),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $screen->seats()->insert($rows);

            return count($rows);
        });
    }

    public static function rowLabel(int $index): string
    {
        $label = '';
        $index++;

        while ($index > 0) {
            $index--;
            $label = chr(65 + ($index % 26)) . $label;
            $index = intdiv($index, 26);
        }

        return $label;
    }
}