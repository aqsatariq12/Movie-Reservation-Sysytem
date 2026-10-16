<?php

namespace App\Models;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'duration_minutes',
        'rating',
        'poster_path',
        'trailer_url',
        'release_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'status' => MovieStatus::class,
        ];
    }

    /** Use the slug in URLs: /movies/the-last-horizon */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /* ---------------------------------------------------------------
     | Query scopes (used by the home page and the movie catalogue)
     * ------------------------------------------------------------- */

    public function scopeNowShowing(Builder $query): void
    {
        $query->where('status', MovieStatus::NowShowing->value);
    }

    public function scopeComingSoon(Builder $query): void
    {
        $query->where('status', MovieStatus::ComingSoon->value);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->where('title', 'like', '%'.$term.'%');
        }
    }

    public function scopeInGenre(Builder $query, ?string $genreSlug): void
    {
        if ($genreSlug) {
            $query->whereHas('genres', fn (Builder $genres) => $genres->where('genres.slug', $genreSlug));
        }
    }

    /**
     * Full URL of the poster. Seeded posters live in public/images/...,
     * admin uploads (later) live on the "public" storage disk.
     */
    protected function posterUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->poster_path) {
                return null;
            }

            return str_starts_with($this->poster_path, 'images/')
                ? asset($this->poster_path)
                : Storage::disk('public')->url($this->poster_path);
        });
    }
}
