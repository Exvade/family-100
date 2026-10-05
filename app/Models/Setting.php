<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const TIMER_DURATION = 'family_100_timer_duration';

    public const DEFAULT_TIMER_DURATION = 60;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, string|int $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /** Durasi timer Family 100 dalam detik. */
    public static function timerDuration(): int
    {
        return (int) static::get(self::TIMER_DURATION, (string) self::DEFAULT_TIMER_DURATION);
    }
}
