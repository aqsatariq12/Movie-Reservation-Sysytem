<?php

namespace App\Models;

use App\Actions\Screens\GenerateSeatsAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Screen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_rows',
        'seats_per_row',
    ];

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function rowRange(): string
    {
        $first = GenerateSeatsAction::rowLabel(0);
        $last = GenerateSeatsAction::rowLabel($this->total_rows - 1);

        return $first === $last ? $first : $first . '–' . $last;
    }

    public function premiumRowLabels(): array
    {
        return $this->seats()
            ->where('type', 'premium')
            ->distinct()
            ->orderBy('row_label')
            ->pluck('row_label')
            ->all();
    }

    public function premiumRowsSummary(): string
    {
        $labels = $this->premiumRowLabels();

        if (
            count($labels) > 1
            && ord(end($labels)) - ord($labels[0]) === count($labels) - 1
        ) {
            return $labels[0] . '–' . end($labels);
        }

        return implode(', ', $labels);
    }

    /**
     * Returns reserved seat IDs mapped to their status.
     *
     * @return Collection<int, string>
     */
    public function reservedSeats(): Collection
    {
        return ShowtimeSeat::query()
            ->whereIn('seat_id', $this->seats()->select('id'))
            ->whereHas('showtime', fn ($query) => $query
                ->where('status', 'scheduled')
                ->where('starts_at', '>', now()))
            ->where(fn ($query) => $query
                ->where('status', 'sold')
                ->orWhere(fn ($held) => $held
                    ->where('status', 'held')
                    ->where('held_until', '>', now())))
            ->orderBy('status')
            ->get(['seat_id', 'status'])
            ->mapWithKeys(fn ($row) => [
                $row->seat_id => $row->status,
            ]);
    }
}
