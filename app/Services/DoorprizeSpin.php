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
 *
 * Mendukung 2 mode pengundian:
 * 1. Mode "quota" (SETTING LUCKY DRAW): Kuota pemenang per kategori diatur di form (mis. 1 CPP, 1 CPW, dst).
 *    Saat undian dihentikan, pemenang dipilih persis sesuai kuota masing-masing kategori.
 * 2. Mode "category" (Trigger per Kategori / Slot Bebas): Admin memilih kategori dan jumlah slot (1 s/d 10).
 */
class DoorprizeSpin
{
    public const SLOTS = 5;

    public const DEFAULT_SLOTS = 5;

    public const MIN_SLOTS = 1;

    public const MAX_SLOTS = 10;

    public const MAX_DURATION = 300;

    private const KEY = 'doorprize_spin';

    private const DURATION_KEY = 'doorprize_spin_duration';

    private const QUOTA_KEY = 'doorprize_quota_setting';

    /** Pengaturan kuota default sesuai permintaan Mas Sarya */
    public function defaultQuotaSetting(): array
    {
        return [
            'Keluarga CPP' => 1,
            'Keluarga CPW' => 1,
            'Teman CPW' => 1,
            'Teman CPP' => 1,
            'Umum' => 1,
        ];
    }

    /** Ambil setting kuota lucky draw yang tersimpan */
    public function getQuotaSetting(): array
    {
        $raw = json_decode(Setting::get(self::QUOTA_KEY) ?? '', true);
        if (! is_array($raw)) {
            return $this->defaultQuotaSetting();
        }

        $result = [];
        $defaults = $this->defaultQuotaSetting();
        foreach (Participant::CATEGORIES as $cat) {
            // Check direct or canonical key
            $val = null;
            if (isset($raw[$cat])) {
                $val = $raw[$cat];
            } else {
                foreach ($raw as $k => $v) {
                    if (Participant::canonicalCategory($k) === $cat) {
                        $val = $v;
                        break;
                    }
                }
            }
            $result[$cat] = $val !== null ? max(0, min(self::MAX_SLOTS, (int) $val)) : ($defaults[$cat] ?? 0);
        }

        return $result;
    }

    /** Simpan setting kuota lucky draw (Tamu Keluarga CPP [X], dst) */
    public function saveQuotaSetting(array $quotas): array
    {
        $clean = [];
        $total = 0;
        foreach (Participant::CATEGORIES as $cat) {
            $val = 0;
            if (isset($quotas[$cat])) {
                $val = (int) $quotas[$cat];
            } else {
                foreach ($quotas as $k => $v) {
                    if (Participant::canonicalCategory($k) === $cat) {
                        $val = (int) $v;
                        break;
                    }
                }
            }
            $val = max(0, min(self::MAX_SLOTS, $val));
            $clean[$cat] = $val;
            $total += $val;
        }

        if ($total < self::MIN_SLOTS) {
            throw new DomainException('Total pemenang minimal 1 orang.');
        }
        if ($total > self::MAX_SLOTS) {
            throw new DomainException('Total pemenang maksimal '.self::MAX_SLOTS.' orang.');
        }

        Setting::put(self::QUOTA_KEY, json_encode($clean, JSON_UNESCAPED_UNICODE));

        // Bila sedang idle, perbarui juga slot aktif ke total kuota
        $raw = $this->raw();
        if ($raw['status'] === 'idle') {
            $this->configure($total, array_keys(array_filter($clean, fn ($q) => $q > 0)));
        }

        return $this->snapshot();
    }

    /** Status undian saat ini */
    public function state(): array
    {
        $this->finishIfExpired();

        return $this->snapshot();
    }

    /** Simpan pengaturan slot & kategori saat idle */
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
                $raw['winner_details'],
                $raw['ends_at_ms'],
                $slots,
                $canonicalCats,
                $raw['mode'],
                $raw['quotas'],
            );
        });
    }

    /**
     * Mulai undian:
     * - Bila mode = "quota": memakai kuota pemenang per kategori yang di-setting.
     * - Bila mode = "category": mengundi $slots pemenang dari $categories yang dipilih.
     */
    public function start(?int $slots = null, ?array $categories = null, ?string $mode = null, ?array $quotas = null): array
    {
        $this->finishIfExpired();

        return DB::transaction(function () use ($slots, $categories, $mode, $quotas) {
            $this->lock();
            $raw = $this->raw();

            if ($raw['status'] === 'spinning') {
                return $this->snapshot();
            }

            // Tentukan mode undian: default ke 'category' bila tidak secara khusus minta 'quota'
            $activeMode = $mode ?? ($quotas !== null ? 'quota' : 'category');

            if ($activeMode === 'quota') {
                $effectiveQuotas = $quotas ?? $this->getQuotaSetting();
                $cleanQuotas = [];
                $totalSlots = 0;
                $activeCats = [];

                foreach (Participant::CATEGORIES as $cat) {
                    $cnt = 0;
                    if (isset($effectiveQuotas[$cat])) {
                        $cnt = (int) $effectiveQuotas[$cat];
                    } else {
                        foreach ($effectiveQuotas as $k => $v) {
                            if (Participant::canonicalCategory($k) === $cat) {
                                $cnt = (int) $v;
                                break;
                            }
                        }
                    }
                    $cnt = max(0, min(self::MAX_SLOTS, $cnt));
                    $cleanQuotas[$cat] = $cnt;
                    if ($cnt > 0) {
                        $totalSlots += $cnt;
                        $activeCats[] = $cat;

                        // Validasi peserta cukup di kategori ini
                        $eligible = $this->eligibleCount([$cat]);
                        if ($eligible < $cnt) {
                            $label = Participant::displayLabel($cat);
                            throw new DomainException("Peserta belum menang pada kategori '{$label}' tidak cukup (dibutuhkan {$cnt}, tersisa {$eligible}). Tambah peserta terlebih dahulu.");
                        }
                    }
                }

                if ($totalSlots < self::MIN_SLOTS) {
                    throw new DomainException('Total pemenang minimal 1 orang.');
                }
                if ($totalSlots > self::MAX_SLOTS) {
                    throw new DomainException('Total pemenang maksimal '.self::MAX_SLOTS.' orang.');
                }

                $effectiveSlots = $totalSlots;
                $effectiveCats = $activeCats;
                $storedQuotas = $cleanQuotas;
            } else {
                $effectiveSlots = $slots !== null
                    ? max(self::MIN_SLOTS, min(self::MAX_SLOTS, $slots))
                    : $raw['slots'];

                $effectiveCats = $categories !== null
                    ? $this->canonicalCategories($categories)
                    : $raw['categories'];

                $this->assertEnoughParticipants($effectiveSlots, $effectiveCats);
                $storedQuotas = [];
            }

            $duration = $this->duration();

            return $this->save(
                'spinning',
                $raw['seq'] + 1,
                [],
                [],
                $duration > 0 ? now()->getTimestampMs() + $duration * 1000 : null,
                $effectiveSlots,
                $effectiveCats,
                $activeMode,
                $storedQuotas,
            );
        });
    }

    /** Hentikan undian dan pilih pemenang sesuai mode aktif */
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

            $mode = $raw['mode'] ?? 'category';
            $slots = $raw['slots'];
            $categories = $raw['categories'];
            $quotas = $raw['quotas'];

            $allPicked = collect();

            if ($mode === 'quota' && ! empty($quotas)) {
                // Pilih pemenang persis sesuai kuota masing-masing kategori
                foreach ($quotas as $cat => $count) {
                    if ($count <= 0) {
                        continue;
                    }
                    $picked = Participant::eligible()
                        ->inCategory($cat)
                        ->inRandomOrder()
                        ->limit($count)
                        ->get(['id', 'name', 'category']);

                    $allPicked = $allPicked->concat($picked);
                }
            } else {
                $this->assertEnoughParticipants($slots, $categories);

                $query = Participant::eligible();
                if (! empty($categories)) {
                    $query->inCategory($categories);
                }

                $allPicked = $query->inRandomOrder()->limit($slots)->get(['id', 'name', 'category']);
            }

            // Tandai pemenang
            if ($allPicked->isNotEmpty()) {
                Participant::whereKey($allPicked->pluck('id'))->update(['won_at' => now()]);
            }

            $winnerNames = $allPicked->pluck('name')->all();
            $winnerDetails = $allPicked->map(fn ($p) => [
                'name' => $p->name,
                'category' => Participant::displayLabel($p->category),
            ])->values()->all();

            return $this->save(
                'stopped',
                $raw['seq'] + 1,
                $winnerNames,
                $winnerDetails,
                null,
                count($winnerNames),
                $categories,
                $mode,
                $quotas,
            );
        });
    }

    /** Reset semua pemenang dan kembalikan undian ke idle */
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
            $this->save('idle', $raw['seq'] + 1, [], [], null, $raw['slots'], $raw['categories'], $raw['mode'], $raw['quotas']);

            return $count;
        });
    }

    /** Jumlah peserta yang sudah pernah menang */
    public function wonCount(): int
    {
        return Participant::whereNotNull('won_at')->count();
    }

    /** Jumlah peserta yang belum pernah menang */
    public function eligibleCount(?array $categories = null): int
    {
        $query = Participant::eligible();
        $categories = $categories !== null ? $this->canonicalCategories($categories) : [];
        if (! empty($categories)) {
            $query->inCategory($categories);
        }

        return $query->count();
    }

    /** Rekap jumlah peserta belum menang per kategori */
    public function eligibleByCategory(): array
    {
        $counts = [];
        foreach (Participant::CATEGORIES as $cat) {
            $counts[$cat] = Participant::eligible()->inCategory($cat)->count();
        }

        return $counts;
    }

    /** Nama peserta yang boleh diundi; dipakai TV untuk animasi putar */
    public function pool(?array $categories = null): Collection
    {
        $raw = $this->raw();
        $effectiveCats = $categories !== null
            ? $this->canonicalCategories($categories)
            : ($raw['status'] === 'spinning' && ! empty($raw['categories']) ? $raw['categories'] : []);

        $query = Participant::eligible();
        if (! empty($effectiveCats)) {
            $query->inCategory($effectiveCats);
        }

        return $query->orderBy('id')->pluck('name');
    }

    public function assertEnoughParticipants(int $slots, array $categories = []): void
    {
        $eligible = $this->eligibleCount($categories);

        if ($eligible < $slots) {
            $catLabel = ! empty($categories) ? ' dari kategori terpilih ('.implode(', ', $categories).')' : '';
            throw new DomainException('Minimal '.$slots.' peserta yang belum menang diperlukan untuk undian'.$catLabel.' (tersisa '.$eligible.'). Tambah peserta terlebih dahulu.');
        }
    }

    public function duration(): int
    {
        return (int) Setting::get(self::DURATION_KEY, '0');
    }

    public function setDuration(int $seconds): void
    {
        if ($seconds < 0 || $seconds > self::MAX_DURATION) {
            throw new InvalidArgumentException('Durasi harus antara 0 dan '.self::MAX_DURATION.' detik.');
        }

        Setting::put(self::DURATION_KEY, $seconds);
    }

    private function finishIfExpired(): void
    {
        $raw = $this->raw();

        if ($raw['status'] !== 'spinning' || $raw['ends_at_ms'] === null || now()->getTimestampMs() < $raw['ends_at_ms']) {
            return;
        }

        try {
            $this->stop();
        } catch (DomainException) {
            DB::transaction(fn () => $this->save('idle', $raw['seq'] + 1, [], [], null, $raw['slots'], $raw['categories'], $raw['mode'], $raw['quotas']));
        }
    }

    private function raw(): array
    {
        $raw = json_decode(Setting::get(self::KEY) ?? '', true);
        $quotaSetting = $this->getQuotaSetting();

        return [
            'status' => $raw['status'] ?? 'idle',
            'seq' => (int) ($raw['seq'] ?? 0),
            'winners' => array_values($raw['winners'] ?? []),
            'winner_details' => array_values($raw['winner_details'] ?? []),
            'slots' => max(self::MIN_SLOTS, min(self::MAX_SLOTS, (int) ($raw['slots'] ?? self::DEFAULT_SLOTS))),
            'categories' => isset($raw['categories']) && is_array($raw['categories'])
                ? array_values(array_filter($raw['categories']))
                : [],
            'mode' => $raw['mode'] ?? 'category',
            'quotas' => isset($raw['quotas']) && is_array($raw['quotas']) ? $raw['quotas'] : $quotaSetting,
            'ends_at_ms' => isset($raw['ends_at_ms']) ? (int) $raw['ends_at_ms'] : null,
        ];
    }

    private function snapshot(): array
    {
        $raw = $this->raw();
        $spinning = $raw['status'] === 'spinning' && $raw['ends_at_ms'] !== null;
        $quotaSetting = $this->getQuotaSetting();

        return [
            'status' => $raw['status'],
            'seq' => $raw['seq'],
            'winners' => $raw['winners'],
            'winner_details' => $raw['winner_details'],
            'slots' => $raw['slots'],
            'categories' => $raw['categories'],
            'mode' => $raw['mode'],
            'quotas' => $raw['quotas'],
            'quota_setting' => $quotaSetting,
            'quota_total' => array_sum($quotaSetting),
            'eligible' => $this->eligibleCount($raw['categories']),
            'eligible_total' => $this->eligibleCount(),
            'eligible_by_category' => $this->eligibleByCategory(),
            'won' => $this->wonCount(),
            'duration' => $this->duration(),
            'remaining_ms' => $spinning ? max(0, $raw['ends_at_ms'] - now()->getTimestampMs()) : null,
        ];
    }

    private function lock(): void
    {
        Setting::where('key', self::KEY)->lockForUpdate()->first();
    }

    private function save(string $status, int $seq, array $winners, array $winnerDetails, ?int $endsAtMs, int $slots, array $categories, string $mode, array $quotas): array
    {
        Setting::put(self::KEY, json_encode([
            'status' => $status,
            'seq' => $seq,
            'winners' => $winners,
            'winner_details' => $winnerDetails,
            'ends_at_ms' => $endsAtMs,
            'slots' => $slots,
            'categories' => $categories,
            'mode' => $mode,
            'quotas' => $quotas,
        ], JSON_UNESCAPED_UNICODE));

        return $this->snapshot();
    }

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

        if (count($valid) >= count(Participant::CATEGORIES)) {
            return [];
        }

        return $valid;
    }
}
