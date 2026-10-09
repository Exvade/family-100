<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    public const DEFAULT_COUNT = 15;
    public const MAX_COUNT = 20;
    public const MIN_COUNT = 1;

    protected $fillable = [
        'number',
        'name',
        'description',
        'is_opened',
        'winner_name',
        'opened_at',
    ];

    protected $casts = [
        'number' => 'integer',
        'is_opened' => 'boolean',
        'opened_at' => 'datetime',
    ];

    /**
     * Memastikan sejumlah $targetCount kotak hadiah (1 s/d $targetCount) tersedia di database.
     */
    public static function ensureCount(int $targetCount = self::DEFAULT_COUNT): void
    {
        $targetCount = max(self::MIN_COUNT, min(self::MAX_COUNT, $targetCount));

        for ($i = 1; $i <= $targetCount; $i++) {
            static::firstOrCreate(
                ['number' => $i],
                [
                    'name' => "Hadiah #{$i}",
                    'description' => null,
                    'is_opened' => false,
                ]
            );
        }
    }

    /**
     * Tutup kembali semua hadiah untuk babak baru.
     */
    public static function resetAll(): void
    {
        static::query()->update([
            'is_opened' => false,
            'winner_name' => null,
            'opened_at' => null,
        ]);
    }
}
