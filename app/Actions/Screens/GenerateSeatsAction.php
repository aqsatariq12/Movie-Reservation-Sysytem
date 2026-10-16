<?php

namespace App\Actions\Screens;

use App\Models\Screen;
use Illuminate\Support\Facades\DB;

class GenerateSeatsAction
{
    public function execute(Screen $screen): int
    {
        return DB::transaction(function () use ($screen) {
            $now = now();
            $rows = [];

            for ($r = 0; $r < $screen->total_rows; $r++) {
                $label = $this->rowLabel($r);

                for ($n = 1; $n <= $screen->seats_per_row; $n++) {
                    $rows[] = [
                        'screen_id'   => $screen->id,
                        'row_label'   => $label,
                        'seat_number' => $n,
                        'type'        => 'standard',
                        'is_disabled' => false,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }

            $screen->seats()->insert($rows);

            return count($rows);
        });
    }

    private function rowLabel(int $index): string
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