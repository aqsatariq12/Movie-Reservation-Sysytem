<?php

namespace App\Models;

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
}