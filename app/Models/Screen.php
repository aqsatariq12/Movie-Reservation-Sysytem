<?php

namespace App\Models;

use App\Actions\Screens\GenerateSeatsAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /** "G–H" when rows are consecutive, otherwise "A, C" */
    public function premiumRowsSummary(): string
    {
        $labels = $this->premiumRowLabels();

        if (count($labels) > 1 && ord(end($labels)) - ord($labels[0]) === count($labels) - 1) {
            return $labels[0] . '–' . end($labels);
        }

        return implode(', ', $labels);
    }

    /**
     * Seats of this screen that are sold, or actively held, for an upcoming showtime.
     * These seats are protected: they cannot be disabled.
     *
     * @return Collection<int, string>  seat_id => 'sold' | 'held'
     */
    public function reservedSeats(): Collection
    {
        return ShowtimeSeat::query()
            ->whereIn('seat_id', $this->seats()->select('id'))
            ->whereHas('showtime', fn($q) => $q
                ->where('status', 'scheduled')
                ->where('starts_at', '>', now()))
            ->where(fn($q) => $q
                ->where('status', 'sold')
                ->orWhere(fn($h) => $h
                    ->where('status', 'held')
                    ->where('held_until', '>', now())))
            ->orderBy('status')
            ->get(['seat_id', 'status'])
            ->mapWithKeys(fn($row) => [$row->seat_id => $row->status]);
    }
}