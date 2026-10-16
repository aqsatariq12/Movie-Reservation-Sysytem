<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value booking rules (hold_minutes, max_seats_per_booking, ...).
 *
 * Usage:  Setting::readInt('hold_minutes', 10)
 *
 * The helpers are named read/readInt/write on purpose: Model::get() and
 * Model::value() already exist on the query builder and would be shadowed.
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function read(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function readInt(string $key, int $default = 0): int
    {
        return (int) static::read($key, (string) $default);
    }

    public static function write(string $key, string|int $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
