<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Prize;
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

    /** Kuota default: semua 0, operator mengisi sendiri sebelum tiap undian */
    public function defaultQuotaSetting(): array
    {
        return array_fill_keys(Participant::CATEGORIES, 0);
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

        // Kategori tanpa peserta yang belum menang tidak ditampilkan di form kuota, jadi kuotanya dianggap 0.
        foreach ($this->eligibleByCategory() as $cat => $eligible) {
            if ($eligible < 1) {
                $result[$cat] = 0;
            }
        }

        return $result;
    }

    /** Kembalikan kuota tersimpan ke 0 untuk kategori yang diberikan */
    private function resetQuotaSetting(array $categories): void
    {
        $setting = $this->getQuotaSetting();
        foreach ($categories as $cat) {
            $setting[$cat] = 0;
        }

        Setting::put(self::QUOTA_KEY, json_encode($setting, JSON_UNESCAPED_UNICODE));
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
                $raw['prize_plan'],
            );
        });
    }

    /**
     * Mulai undian:
     * - Bila mode = "quota": memakai kuota pemenang per kategori yang di-setting.
     * - Bila mode = "category": mengundi $slots pemenang dari $categories yang dipilih.
     */
    public function start(?int $slots = null, ?array $categories = null, ?string $mode = null, ?array $quotas = null, ?int $prizeId = null, ?array $allocation = null): array
    {
        $this->finishIfExpired();

        return DB::transaction(function () use ($slots, $categories, $mode, $quotas, $prizeId, $allocation) {
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
                $requestedSlots = 0;
                $quotaCats = [];
                $explicitQuotas = $quotas !== null;

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
                    $requestedSlots += $cnt;
                    if ($cnt > 0) {
                        $quotaCats[] = $cat;
                    }

                    // Jatah kategori dibatasi sisa pesertanya; kekurangannya diisi saat undian dihentikan.
                    $cleanQuotas[$cat] = min($cnt, $this->eligibleCount([$cat]));
                }

                if ($requestedSlots < self::MIN_SLOTS) {
                    throw new DomainException('Total pemenang minimal 1 orang.');
                }
                if ($requestedSlots > self::MAX_SLOTS) {
                    throw new DomainException('Total pemenang maksimal '.self::MAX_SLOTS.' orang.');
                }

                // Slot di TV mengikuti total kuota, tapi tak bisa melebihi peserta yang belum menang di kategori berkuota.
                $totalSlots = min($requestedSlots, $this->eligibleCount($quotaCats));
                if ($totalSlots < self::MIN_SLOTS) {
                    throw new DomainException('Tidak ada peserta yang belum menang pada kategori yang berkuota. Tambah peserta terlebih dahulu.');
                }

                $effectiveSlots = $totalSlots;
                $effectiveCats = $quotaCats;
                $storedQuotas = $cleanQuotas;
                $prizePlan = $this->buildPrizePlan($prizeId, $allocation, $effectiveSlots);

                // Kuota yang dipakai undian ini kembali ke 0: semuanya bila memakai kuota tersimpan,
                // atau hanya kategori yang diundi bila kuota dikirim eksplisit (trigger cepat).
                $this->resetQuotaSetting($explicitQuotas ? $quotaCats : Participant::CATEGORIES);
            } else {
                $effectiveSlots = $slots !== null
                    ? max(self::MIN_SLOTS, min(self::MAX_SLOTS, $slots))
                    : $raw['slots'];

                $effectiveCats = $categories !== null
                    ? $this->canonicalCategories($categories)
                    : $raw['categories'];

                $this->assertEnoughParticipants($effectiveSlots, $effectiveCats);
                $prizePlan = $this->buildPrizePlan($prizeId, $allocation, $effectiveSlots);
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
                $prizePlan,
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

                // Kategori yang pesertanya kurang: sisa slot diisi acak dari peserta lain di kategori berkuota.
                $missing = $slots - $allPicked->count();
                if ($missing > 0) {
                    $fill = Participant::eligible()
                        ->inCategory($categories)
                        ->whereNotIn('id', $allPicked->pluck('id'))
                        ->inRandomOrder()
                        ->limit($missing)
                        ->get(['id', 'name', 'category']);

                    $allPicked = $allPicked->concat($fill);
                }
            } else {
                $this->assertEnoughParticipants($slots, $categories);

                $query = Participant::eligible();
                if (! empty($categories)) {
                    $query->inCategory($categories);
                }

                $allPicked = $query->inRandomOrder()->limit($slots)->get(['id', 'name', 'category']);
            }

            // Rencana hadiah per slot: pemenang di slot ke-n mendapat hadiah ke-n. Urutan pemenang diacak dulu
            // supaya posisi slot (dan hadiah utama) tidak ditentukan oleh urutan kategori.
            $plan = $raw['prize_plan'];
            if (! empty($plan)) {
                $allPicked = $allPicked->shuffle()->values();
            }

            $prizeNames = Prize::whereIn('id', array_unique($plan))->pluck('name', 'id');
            $prizeForSlot = fn (int $i): ?int => isset($plan[$i]) && $prizeNames->has($plan[$i]) ? $plan[$i] : null;

            // Tandai pemenang dan tautkan ke hadiahnya (hadiah yang sudah dihapus saat berputar dilewati)
            if ($allPicked->isNotEmpty()) {
                if (empty($plan)) {
                    Participant::whereKey($allPicked->pluck('id'))->update(['won_at' => now()]);
                } else {
                    foreach ($allPicked as $i => $p) {
                        Participant::whereKey($p->id)->update(['won_at' => now(), 'prize_id' => $prizeForSlot($i)]);
                    }
                }
            }

            $winnerNames = $allPicked->pluck('name')->all();
            $winnerDetails = $allPicked->map(fn ($p, $i) => [
                'name' => $p->name,
                'category' => Participant::displayLabel($p->category),
                'prize' => $prizeForSlot($i) !== null ? $prizeNames[$prizeForSlot($i)] : null,
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
                $plan,
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

            $count = Participant::whereNotNull('won_at')->update(['won_at' => null, 'prize_id' => null]);
            $this->save('idle', $raw['seq'] + 1, [], [], null, $raw['slots'], $raw['categories'], $raw['mode'], $raw['quotas']);

            return $count;
        });
    }

    /** Jumlah peserta yang sudah pernah menang */
    public function wonCount(): int
    {
        return Participant::whereNotNull('won_at')->count();
    }

    /** Daftar riwayat seluruh pemenang doorprize yang pernah menang */
    public function winnersHistory(): array
    {
        return Participant::whereNotNull('won_at')
            ->orderBy('won_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($p, $idx) {
                return [
                    'no' => $idx + 1,
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category,
                    'category_label' => Participant::displayLabel($p->category),
                    'won_at' => $p->won_at?->toIso8601String(),
                    'won_at_human' => $p->won_at?->diffForHumans() ?? '-',
                    'won_at_formatted' => $p->won_at?->translatedFormat('d M Y, H:i') ?? '-',
                ];
            })
            ->values()
            ->all();
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

    /**
     * Rencana hadiah per slot (hadiah ke-n untuk pemenang di slot ke-n). `$allocation` berisi baris
     * [prize_id, count] berurutan; `$prizeId` saja berarti satu hadiah untuk semua slot. Tanpa keduanya
     * undian berjalan tanpa hadiah. Jumlah alokasi harus sama dengan jumlah slot dan stok tiap hadiah cukup.
     *
     * @return list<int>
     */
    private function buildPrizePlan(?int $prizeId, ?array $allocation, int $slots): array
    {
        if ($allocation === null && $prizeId === null) {
            return [];
        }

        $allocation ??= [['prize_id' => $prizeId, 'count' => $slots]];

        $plan = [];
        $needed = [];
        foreach ($allocation as $row) {
            $id = (int) ($row['prize_id'] ?? 0);
            $count = (int) ($row['count'] ?? 0);
            for ($i = 0; $i < $count; $i++) {
                $plan[] = $id;
            }
            if ($count > 0) {
                $needed[$id] = ($needed[$id] ?? 0) + $count;
            }
        }

        if (count($plan) !== $slots) {
            throw new DomainException('Alokasi hadiah ('.count($plan).') harus sama dengan jumlah pemenang ('.$slots.').');
        }

        $prizes = Prize::whereIn('id', array_keys($needed))->get()->keyBy('id');
        foreach ($needed as $id => $count) {
            $prize = $prizes->get($id);
            if ($prize === null) {
                throw new DomainException('Hadiah yang dipilih tidak ditemukan. Pilih hadiah lagi.');
            }

            $remaining = $prize->remaining();
            if ($remaining < $count) {
                throw new DomainException("Sisa hadiah '{$prize->name}' hanya {$remaining}, kurang dari {$count} yang dialokasikan. Tambah jumlah hadiah atau ubah alokasi.");
            }
        }

        return $plan;
    }

    /** Daftar hadiah beserta stok dan pemenangnya */
    public function prizes(): array
    {
        return Prize::with('winners:id,name,prize_id,won_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Prize $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'image_url' => $p->imageUrl(),
                'awarded' => $p->winners->count(),
                'remaining' => max(0, $p->quantity - $p->winners->count()),
                'winners' => $p->winners->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();
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
            'prize_plan' => isset($raw['prize_plan']) && is_array($raw['prize_plan']) ? array_map('intval', array_values($raw['prize_plan'])) : [],
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
            'prize_plan' => $raw['prize_plan'],
            'prizes' => $this->prizes(),
            'won' => $this->wonCount(),
            'winners_history' => $this->winnersHistory(),
            'duration' => $this->duration(),
            'remaining_ms' => $spinning ? max(0, $raw['ends_at_ms'] - now()->getTimestampMs()) : null,
        ];
    }

    private function lock(): void
    {
        Setting::where('key', self::KEY)->lockForUpdate()->first();
    }

    private function save(string $status, int $seq, array $winners, array $winnerDetails, ?int $endsAtMs, int $slots, array $categories, string $mode, array $quotas, array $prizePlan = []): array
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
            'prize_plan' => $prizePlan,
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
