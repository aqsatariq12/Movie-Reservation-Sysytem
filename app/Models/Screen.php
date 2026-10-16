<?php

namespace App\Models;

use App\Actions\Screens\GenerateSeatsAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    protected $fillable = [
        'name',
        'total_rows',
        'seats_per_row',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'seats_per_row' => 'integer',
        ];
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /** e.g. "A–H" */
    public function rowRange(): string
    {
        $first = GenerateSeatsAction::rowLabel(0);
        $last = GenerateSeatsAction::rowLabel($this->total_rows - 1);

        return $first === $last ? $first : $first . '–' . $last;
    }

    /** @return array<int, string> e.g. ['G', 'H'] */
    public function premiumRowLabels(): array
    {
        return $this->seats()
            ->where('type', 'premium')
            ->distinct()
            ->orderBy('row_label')
            ->pluck('row_label')
            ->all();
    }
}