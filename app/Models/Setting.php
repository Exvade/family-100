<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const TIMER_DURATION = 'family_100_timer_duration';

    public const ACTIVE_QUESTION = 'family_100_active_question_id';

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

    public const TV_MODE = 'family_100_tv_mode';

    public const TV_MODE_QUIZ = 'quiz';

    public const TV_MODE_GIFT = 'gift';

    public const GIFT_COUNT = 'family_100_gift_count';

    public const DEFAULT_GIFT_COUNT = 15;

    public static function tvMode(): string
    {
        return static::get(self::TV_MODE, self::TV_MODE_QUIZ) ?: self::TV_MODE_QUIZ;
    }

    public static function giftCount(): int
    {
        $count = (int) static::get(self::GIFT_COUNT, (string) self::DEFAULT_GIFT_COUNT);

        return max(1, min(20, $count ?: self::DEFAULT_GIFT_COUNT));
    }

    /** Durasi timer Family 100 dalam detik. */
    public static function timerDuration(): int
    {
        return (int) static::get(self::TIMER_DURATION, (string) self::DEFAULT_TIMER_DURATION);
    }
}
