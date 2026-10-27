<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogPresenter
{
    /** Field names that must never be displayed. */
    private const SENSITIVE = '/password|secret|token|api_key|card/i';

    /** "settings.updated" => "Settings updated" */
    public static function title(string $action): string
    {
        return Str::ucfirst(str_replace(['.', '_'], ' ', $action));
    }

    /**
     * Human-readable target for every entry, using one query per entity type.
     *
     * @param  Collection<int, AuditLog>  $logs
     * @return array<int, string>  log id => label
     */
    public static function targets(Collection $logs): array
    {
        $ids = fn (string $type) => $logs->where('entity_type', $type)->pluck('entity_id')->filter()->unique()->all();

        $users = User::whereIn('id', $ids('user'))->pluck('name', 'id');
        $movies = DB::table('movies')->whereIn('id', $ids('movie'))->pluck('title', 'id');
        $showtimes = DB::table('showtimes')
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->whereIn('showtimes.id', $ids('showtime'))
            ->get(['showtimes.id', 'showtimes.starts_at', 'movies.title'])
            ->keyBy('id');

        return $logs->mapWithKeys(function (AuditLog $log) use ($users, $movies, $showtimes) {
            $id = $log->entity_id;

            $label = match ($log->entity_type) {
                'settings'   => 'Settings',
                'user'       => $users[$id] ?? "User #{$id}",
                'movie'      => $movies[$id] ?? "Movie #{$id}",
                'showtime'   => isset($showtimes[$id])
                    ? $showtimes[$id]->title . ' · ' . Carbon::parse($showtimes[$id]->starts_at)->format('M j / H:i')
                    : "Showtime #{$id}",
                'failed_job' => ($log->before_data['reference'] ?? null) ?: ($log->before_data['job_class'] ?? 'Failed job'),
                default      => Str::headline($log->entity_type) . ($id ? " #{$id}" : ''),
            };

            return [$log->id => $label];
        })->all();
    }

    /**
     * Rows for the before / after table.
     *
     * @return array<int, array{field: string, before: string, after: string}>
     */
    public static function changes(?array $before, ?array $after): array
    {
        $before ??= [];
        $after ??= [];

        return collect(array_unique([...array_keys($before), ...array_keys($after)]))
            ->map(fn ($field) => [
                'field'  => (string) $field,
                'before' => self::value((string) $field, $before[$field] ?? null),
                'after'  => self::value((string) $field, $after[$field] ?? null),
            ])
            ->all();
    }

    private static function value(string $field, mixed $value): string
    {
        if (preg_match(self::SENSITIVE, $field)) {
            return '[hidden]';
        }

        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) && str_ends_with($field, '_cents') => Showtime::money($value),
            is_scalar($value) => (string) $value,
            default => json_encode(self::redact($value), JSON_UNESCAPED_UNICODE),
        };
    }

    /** Hides sensitive keys inside nested data. */
    private static function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = is_string($key) && preg_match(self::SENSITIVE, $key)
                ? '[hidden]'
                : self::redact($item);
        }

        return $value;
    }
}