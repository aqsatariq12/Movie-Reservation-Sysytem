<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
/**
 * Append-only record of sensitive admin/staff actions.
 * The table only has created_at (no updated_at), so UPDATED_AT is disabled.
 * Never store passwords, Stripe secrets or raw QR tokens in before/after data.
 */
class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'before_data',
        'after_data',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Search the "target" of an entry: type, name, movie title, id, or saved data. */
    public function scopeTargetMatches(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like, $term) {
            $q->where('entity_type', 'like', $like)
                ->orWhere(fn($w) => $w->where('entity_type', 'user')->whereIn(
                    'entity_id',
                    DB::table('users')->select('id')->where('name', 'like', $like)->orWhere('email', 'like', $like)
                ))
                ->orWhere(fn($w) => $w->where('entity_type', 'movie')->whereIn(
                    'entity_id',
                    DB::table('movies')->select('id')->where('title', 'like', $like)
                ))
                ->orWhere(fn($w) => $w->where('entity_type', 'showtime')->whereIn(
                    'entity_id',
                    DB::table('showtimes')
                        ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
                        ->select('showtimes.id')
                        ->where('movies.title', 'like', $like)
                ))
                ->orWhere('before_data', 'like', $like)
                ->orWhere('after_data', 'like', $like);

            if (ctype_digit($term)) {
                $q->orWhere('entity_id', (int) $term);
            }
        });
    }
}
