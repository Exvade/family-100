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
        'Teman CPW',
        'Teman CPP',
        'Umum',
    ];

    public const DEFAULT_CATEGORY = 'Umum';

    protected $fillable = ['name', 'category'];

    /** Daftar alias penulisan kategori untuk pencarian fleksibel */
    public static function categoryAliases(string $category): array
    {
        $canon = self::canonicalCategory($category);

        return match ($canon) {
            'Keluarga CPP' => ['Keluarga CPP', 'Tamu Keluarga CPP', 'tamu keluarga cpp', 'keluarga cpp', 'CPP', 'cpp'],
            'Keluarga CPW' => ['Keluarga CPW', 'Tamu Keluarga CPW', 'tamu keluarga cpw', 'keluarga cpw', 'CPW', 'cpw'],
            'Teman CPW' => ['Teman CPW', 'teman cpw', 'Sahabat CPW', 'sahabat cpw'],
            'Teman CPP' => ['Teman CPP', 'Teman CPK', 'teman cpp', 'teman cpk', 'Sahabat CPP', 'sahabat cpp', 'Sahabat CPK', 'sahabat cpk'],
            'Umum' => ['Umum', 'UMUM', 'umum', 'Tamu Umum', 'tamu umum'],
            default => [$category],
        };
    }

    /** Label ramah tampilan sesuai permintaan Mas Sarya */
    public static function displayLabel(string $category): string
    {
        return match (self::canonicalCategory($category)) {
            'Keluarga CPP' => 'Tamu Keluarga CPP',
            'Keluarga CPW' => 'Tamu Keluarga CPW',
            'Teman CPW' => 'Teman CPW',
            'Teman CPP' => 'Teman CPP (CPK)',
            'Umum' => 'Umum',
            default => $category,
        };
    }

    /** Normalisasi nama kategori ke penulisan baku (case-insensitive & alias-friendly) */
    public static function canonicalCategory(?string $category): string
    {
        if ($category === null) {
            return self::DEFAULT_CATEGORY;
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $category));
        $lower = mb_strtolower($trimmed);

        if (in_array($lower, ['keluarga cpp', 'tamu keluarga cpp', 'keluarga pria', 'cpp'], true)) {
            return 'Keluarga CPP';
        }
        if (in_array($lower, ['keluarga cpw', 'tamu keluarga cpw', 'keluarga wanita', 'cpw'], true)) {
            return 'Keluarga CPW';
        }
        if (in_array($lower, ['teman cpw', 'sahabat cpw'], true)) {
            return 'Teman CPW';
        }
        if (in_array($lower, ['teman cpp', 'teman cpk', 'sahabat cpp', 'sahabat cpk'], true)) {
            return 'Teman CPP';
        }
        if (in_array($lower, ['umum', 'tamu umum'], true)) {
            return 'Umum';
        }

        foreach (self::CATEGORIES as $valid) {
            if ($lower === mb_strtolower($valid)) {
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

    /** Filter berdasarkan satu atau beberapa kategori peserta (otomatis mencakup varian/alias penulisan). */
    public function scopeInCategory(Builder $query, array|string $categories): void
    {
        $categories = array_filter((array) $categories);
        if (! empty($categories)) {
            $all = [];
            foreach ($categories as $cat) {
                $all = array_merge($all, self::categoryAliases((string) $cat));
            }
            $query->whereIn('category', array_values(array_unique($all)));
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
