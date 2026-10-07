<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Daftar mobile dengan pencarian, urutan, dan infinite scroll:
 * jumlah baris yang dimuat bertambah tiap sentinel di dasar daftar terlihat.
 */
trait LoadsMore
{
    private const PAGE_SIZE = 8;

    private const MAX_LOADED = 200;

    public string $search = '';

    /** Format "kolom:arah", mis. "ranking:desc". */
    public string $sort = 'no:asc';

    public int $loaded = self::PAGE_SIZE;

    public function loadMore(): void
    {
        $this->loaded += self::PAGE_SIZE;
    }

    public function updatedSearch(): void
    {
        $this->loaded = self::PAGE_SIZE;
    }

    public function updatedSort(): void
    {
        $this->loaded = self::PAGE_SIZE;
    }

    protected function limit(): int
    {
        return max(self::PAGE_SIZE, min($this->loaded, self::MAX_LOADED));
    }

    /**
     * Properti publik bisa diubah dari browser, jadi kolom dan arah divalidasi ke daftar yang diizinkan.
     *
     * @param  list<string>  $allowed
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function sortParts(array $allowed): array
    {
        [$key, $dir] = array_pad(explode(':', $this->sort, 2), 2, 'asc');

        return [in_array($key, $allowed, true) ? $key : $allowed[0], $dir === 'desc' ? 'desc' : 'asc'];
    }

    /** Pencarian LIKE; wildcard di input dinetralkan dengan escape "!" (eksplisit agar sama di MySQL dan SQLite). */
    protected function applySearch(Builder|Relation $query, string $column): void
    {
        $term = trim($this->search);

        if ($term !== '') {
            $query->whereRaw("{$column} like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%']);
        }
    }
}
