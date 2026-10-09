<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** Hadiah doorprize; stok berkurang tiap kali pemenang undian ditautkan ke hadiah ini. */
class Prize extends Model
{
    /** @use HasFactory<\Database\Factories\PrizeFactory> */
    use HasFactory;

    public const MAX_QUANTITY = 999;

    /** Folder gambar hadiah pada disk "public" */
    public const IMAGE_DIR = 'prizes';

    protected $fillable = ['name', 'quantity', 'image_path'];

    /** Peserta yang memenangkan hadiah ini */
    public function winners(): HasMany
    {
        return $this->hasMany(Participant::class)->orderBy('won_at')->orderBy('id');
    }

    /** URL gambar hadiah (relatif terhadap host yang sedang dipakai), null bila belum ada gambar */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    /** Hapus berkas gambar dari disk dan kosongkan kolomnya (tanpa menyimpan model) */
    public function forgetImage(): void
    {
        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
            $this->image_path = null;
        }
    }

    /** Jumlah hadiah yang belum diberikan */
    public function remaining(): int
    {
        return max(0, $this->quantity - $this->winners()->count());
    }
}
