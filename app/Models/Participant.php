<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Peserta undian doorprize. */
class Participant extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipantFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'Keluarga CPP',
        'Keluarga CPW',
        'Teman CPP',
        'Teman CPW',
        'UMUM',
    ];

    public const DEFAULT_CATEGORY = 'UMUM';

    protected $fillable = ['name', 'category'];

    /** Normalisasi nama kategori ke penulisan baku (case-insensitive) */
    public static function canonicalCategory(?string $category): string
    {
        if ($category === null) {
            return self::DEFAULT_CATEGORY;
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $category));
        foreach (self::CATEGORIES as $valid) {
            if (mb_strtolower($trimmed) === mb_strtolower($valid)) {
                return $valid;
            }
        }

        return self::DEFAULT_CATEGORY;
    }

    protected function casts(): array
    {
        return ['won_at' => 'datetime'];
    }

    /** Peserta yang belum pernah menang, jadi masih boleh diundi. */
    public function scopeEligible(Builder $query): void
    {
        $query->whereNull('won_at');
    }

    /** Filter berdasarkan satu atau beberapa kategori peserta. */
    public function scopeInCategory(Builder $query, array|string $categories): void
    {
        $categories = array_filter((array) $categories);
        if (! empty($categories)) {
            $query->whereIn('category', $categories);
        }
    }

    /** Pernah terpilih sebagai pemenang undian (diisi saat undian dihentikan). */
    public function isWinner(): bool
    {
        return $this->won_at !== null;
    }

    /** Nama sama (tanpa membedakan huruf besar/kecil) dengan peserta lain, opsional mengecualikan satu id. */
    public static function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return static::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->exists();
    }
}
