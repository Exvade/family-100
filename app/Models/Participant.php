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

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['won_at' => 'datetime'];
    }

    /** Peserta yang belum pernah menang, jadi masih boleh diundi. */
    public function scopeEligible(Builder $query): void
    {
        $query->whereNull('won_at');
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
