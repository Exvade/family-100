<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Setting;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Status undian doorprize yang dikendalikan dari dasbor dan dibaca layar TV.
 *
 * idle -> (start) spinning -> (stop / waktu habis) stopped -> (start) spinning ...
 * Setiap perubahan menaikkan "seq" supaya TV tahu ada perintah baru. Pemenang dipilih di server saat berhenti.
 *
 * - Hanya peserta yang belum pernah menang (won_at kosong) ikut diundi dan ikut berputar di layar TV.
 * - Jumlah pemenang per putaran dinamis (1 s/d MAX_SLOTS, default DEFAULT_SLOTS).
 * - Kategori peserta yang diundi bisa dipilih (bisa lebih dari satu kategori, atau semua kategori).
 * - Undian butuh minimal N peserta yang belum menang sesuai kategori yang dipilih.
 * - Reset pemenang mengosongkan won_at semua peserta dan mengembalikan status ke idle.
 * - Durasi spin > 0 detik: undian berhenti sendiri setelah waktu habis.
 */
class DoorprizeSpin
{
    public const SLOTS = 5;          // fallback konstan lama untuk kompatibilitas

    public const DEFAULT_SLOTS = 5;

    public const MIN_SLOTS = 1;

    public const MAX_SLOTS = 10;

    public const MAX_DURATION = 300;

    private const KEY = 'doorprize_spin';

    private const DURATION_KEY = 'doorprize_spin_duration';

    /**
     * @return array{status: string, seq: int, winners: list<string>, slots: int, categories: list<string>, eligible: int, eligible_total: int, eligible_by_category: array<string, int>, won: int, duration: int, remaining_ms: int|null}
     */
    public function state(): array
    {
        $this->finishIfExpired();

        return $this->snapshot();
    }

    /**
     * Simpan pengaturan slot & kategori saat idle.
     *
     * @param  list<string>  $categories
     * @return array<string, mixed>
     */
    public function configure(int $slots, array $categories = []): array
    {
        $slots = max(self::MIN_SLOTS, min(self::MAX_SLOTS, $slots));
        $canonicalCats = $this->canonicalCategories($categories);

        return DB::transaction(function () use ($slots, $canonicalCats) {
            $this->lock();
            $raw = $this->raw();

            return $this->save(
                $raw['status'],
                $raw['seq'],
                $raw['winners'],
                $raw['ends_at_ms'],
                $slots,
                $canonicalCats,
            );
        });
    }

    /**
     * Mulai undian dengan jumlah slot dan kategori terpilih.
     *
     * @param  list<string>|null  $categories
     * @return array<string, mixed>
     *
     * @throws DomainException bila peserta yang belum menang kurang dari slots
     */
    public function start(?int $slots = null, ?array $categories = null): array
    {
        $this->finishIfExpired();

        return DB::transaction(function () use ($slots, $categories) {
            $this->lock();
            $raw = $this->raw();

            if ($raw['status'] === 'spinning') {
                return $this->snapshot();   // sudah berputar: jangan mulai ulang
            }

            $effectiveSlots = $slots !== null
                ? max(self::MIN_SLOTS, min(self::MAX_SLOTS, $slots))
                : $raw['slots'];

            $effectiveCats = $categories !== null
                ? $this->canonicalCategories($categories)
                : $raw['categories'];

            $this->assertEnoughParticipants($effectiveSlots, $effectiveCats);

            $duration = $this->duration();

            return $this->save(
                'spinning',
                $raw['seq'] + 1,
                [],
                $duration > 0 ? now()->getTimestampMs() + $duration * 1000 : null,
                $effectiveSlots,
                $effectiveCats,
            );
        });
    }

    /**
     * Hentikan undian dan pilih pemenang.
     *
     * @return array<string, mixed>
     *
     * @throws DomainException bila belum dimulai atau peserta yang belum menang kurang
     */
    public function stop(): array
    {
        return DB::transaction(function () {
            $this->lock();
            $raw = $this->raw();

            if ($raw['status'] === 'stopped') {
                return $this->snapshot();
            }
            if ($raw['status'] !== 'spinning') {
                throw new DomainException('Undian belum dimulai. Klik Start terlebih dahulu.');
            }

            $slots = $raw['slots'];
            $categories = $raw['categories'];

            $this->assertEnoughParticipants($slots, $categories);

            $query = Participant::eligible();
            if (! empty($categories)) {
                $query->whereIn('category', $categories);
            }

            $picked = $query->inRandomOrder()->limit($slots)->get(['id', 'name']);

            // Tersimpan permanen di peserta, jadi statusnya tetap terlihat setelah putaran berikutnya.
            Participant::whereKey($picked->modelKeys())->update(['won_at' => now()]);

            return $this->save(
                'stopped',
                $raw['seq'] + 1,
                $picked->pluck('name')->all(),
                null,
                $slots,
                $categories,
            );
        });
    }

    /**
     * Hapus status pemenang semua peserta (mereka bisa diundi lagi) dan kosongkan layar TV.
     *
     * @throws DomainException bila undian sedang berputar
     */
    public function reset(): int
    {
        $this->finishIfExpired();

        return DB::transaction(function () {
            $this->lock();
            $raw = $this->raw();

            if ($raw['status'] === 'spinning') {
                throw new DomainException('Undian sedang berputar. Klik Stop terlebih dahulu sebelum mereset pemenang.');
            }

            $count = Participant::whereNotNull('won_at')->update(['won_at' => null]);
            $this->save('idle', $raw['seq'] + 1, [], null, $raw['slots'], $raw['categories']);

            return $count;
        });
    }

    /** Jumlah peserta yang sudah pernah menang. */
    public function wonCount(): int
    {
        return Participant::whereNotNull('won_at')->count();
    }

    /** Jumlah peserta yang belum pernah menang, opsional difilter kategori. */
    public function eligibleCount(?array $categories = null): int
    {
        $query = Participant::eligible();
        $categories = $categories !== null ? $this->canonicalCategories($categories) : [];
        if (! empty($categories)) {
            $query->whereIn('category', $categories);
        }

        return $query->count();
    }

    /** Rekap jumlah peserta belum menang per kategori. */
    public function eligibleByCategory(): array
    {
        $counts = [];
        foreach (Participant::CATEGORIES as $cat) {
            $counts[$cat] = Participant::eligible()->where('category', $cat)->count();
        }

        return $counts;
    }

    /** Nama peserta yang boleh diundi; dipakai TV untuk animasi putar. */
    public function pool(?array $categories = null): Collection
    {
        $raw = $this->raw();
        $effectiveCats = $categories !== null
            ? $this->canonicalCategories($categories)
            : ($raw['status'] === 'spinning' && ! empty($raw['categories']) ? $raw['categories'] : []);

        $query = Participant::eligible();
        if (! empty($effectiveCats)) {
            $query->whereIn('category', $effectiveCats);
        }

        return $query->orderBy('id')->pluck('name');
    }

    /** Validasi apakah peserta cukup untuk jumlah slot dan kategori yang diminta. */
    public function assertEnoughParticipants(int $slots, array $categories = []): void
    {
        $eligible = $this->eligibleCount($categories);

        if ($eligible < $slots) {
            $catLabel = ! empty($categories) ? ' dari kategori terpilih ('.implode(', ', $categories).')' : '';
            throw new DomainException('Minimal '.$slots.' peserta yang belum menang diperlukan untuk undian'.$catLabel.' (tersisa '.$eligible.'). Tambah peserta terlebih dahulu.');
        }
    }

    /** Durasi spin otomatis dalam detik; 0 = berputar sampai Stop diklik. */
    public function duration(): int
    {
        return (int) Setting::get(self::DURATION_KEY, '0');
    }

    /** @throws InvalidArgumentException bila di luar 0..MAX_DURATION */
    public function setDuration(int $seconds): void
    {
        if ($seconds < 0 || $seconds > self::MAX_DURATION) {
            throw new InvalidArgumentException('Durasi harus antara 0 dan '.self::MAX_DURATION.' detik.');
        }

        Setting::put(self::DURATION_KEY, $seconds);
    }

    /** Hentikan otomatis bila waktu spin sudah habis. */
    private function finishIfExpired(): void
    {
        $raw = $this->raw();

        if ($raw['status'] !== 'spinning' || $raw['ends_at_ms'] === null || now()->getTimestampMs() < $raw['ends_at_ms']) {
            return;
        }

        try {
            $this->stop();
        } catch (DomainException) {
            // Peserta tidak cukup lagi selama berputar: kembalikan ke awal supaya TV tidak berputar selamanya.
            DB::transaction(fn () => $this->save('idle', $raw['seq'] + 1, [], null, $raw['slots'], $raw['categories']));
        }
    }

    /** @return array{status: string, seq: int, winners: list<string>, slots: int, categories: list<string>, ends_at_ms: int|null} */
    private function raw(): array
    {
        $raw = json_decode(Setting::get(self::KEY) ?? '', true);

        return [
            'status' => $raw['status'] ?? 'idle',
            'seq' => (int) ($raw['seq'] ?? 0),
            'winners' => array_values($raw['winners'] ?? []),
            'slots' => max(self::MIN_SLOTS, min(self::MAX_SLOTS, (int) ($raw['slots'] ?? self::DEFAULT_SLOTS))),
            'categories' => isset($raw['categories']) && is_array($raw['categories'])
                ? array_values(array_filter($raw['categories']))
                : [],
            'ends_at_ms' => isset($raw['ends_at_ms']) ? (int) $raw['ends_at_ms'] : null,
        ];
    }

    /** @return array{status: string, seq: int, winners: list<string>, slots: int, categories: list<string>, eligible: int, eligible_total: int, eligible_by_category: array<string, int>, won: int, duration: int, remaining_ms: int|null} */
    private function snapshot(): array
    {
        $raw = $this->raw();
        $spinning = $raw['status'] === 'spinning' && $raw['ends_at_ms'] !== null;

        return [
            'status' => $raw['status'],
            'seq' => $raw['seq'],
            'winners' => $raw['winners'],
            'slots' => $raw['slots'],
            'categories' => $raw['categories'],
            'eligible' => $this->eligibleCount($raw['categories']),
            'eligible_total' => $this->eligibleCount(),
            'eligible_by_category' => $this->eligibleByCategory(),
            'won' => $this->wonCount(),
            'duration' => $this->duration(),
            'remaining_ms' => $spinning ? max(0, $raw['ends_at_ms'] - now()->getTimestampMs()) : null,
        ];
    }

    /** Kunci baris status agar dua perintah bersamaan tidak memilih pemenang dua kali. */
    private function lock(): void
    {
        Setting::where('key', self::KEY)->lockForUpdate()->first();
    }

    /**
     * @param  list<string>  $winners
     * @param  list<string>  $categories
     * @return array<string, mixed>
     */
    private function save(string $status, int $seq, array $winners, ?int $endsAtMs, int $slots, array $categories): array
    {
        Setting::put(self::KEY, json_encode([
            'status' => $status,
            'seq' => $seq,
            'winners' => $winners,
            'ends_at_ms' => $endsAtMs,
            'slots' => $slots,
            'categories' => $categories,
        ], JSON_UNESCAPED_UNICODE));

        return $this->snapshot();
    }

    /**
     * Normalisasi daftar kategori ke penulisan baku.
     *
     * @param  list<string>  $categories
     * @return list<string>
     */
    private function canonicalCategories(array $categories): array
    {
        $valid = [];
        foreach ($categories as $cat) {
            if (! is_string($cat)) {
                continue;
            }
            $canon = Participant::canonicalCategory($cat);
            if (! in_array($canon, $valid, true)) {
                $valid[] = $canon;
            }
        }

        // Jika semua 5 kategori dicentang, kita simpan list kosong yang berarti "semua"
        if (count($valid) >= count(Participant::CATEGORIES)) {
            return [];
        }

        return $valid;
    }
}
