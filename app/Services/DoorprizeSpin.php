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
 * - Undian butuh minimal SLOTS peserta yang belum menang; kurang dari itu tidak bisa dimulai.
 * - Reset pemenang mengosongkan won_at semua peserta dan mengembalikan status ke idle.
 * - Durasi spin > 0 detik: undian berhenti sendiri setelah waktu habis. Penentuannya di server (dievaluasi saat
 *   status dibaca, yang dilakukan TV tiap detik), bukan di browser, supaya TV dan dasbor selalu sama.
 */
class DoorprizeSpin
{
    public const SLOTS = 5;

    public const MAX_DURATION = 300;

    private const KEY = 'doorprize_spin';

    private const DURATION_KEY = 'doorprize_spin_duration';

    /**
     * @return array{status: string, seq: int, winners: list<string>, eligible: int, won: int, duration: int, remaining_ms: int|null}
     */
    public function state(): array
    {
        $this->finishIfExpired();

        return $this->snapshot();
    }

    /**
     * @return array{status: string, seq: int, winners: list<string>, eligible: int, won: int, duration: int, remaining_ms: int|null}
     *
     * @throws DomainException bila peserta yang belum menang kurang dari SLOTS
     */
    public function start(): array
    {
        $this->finishIfExpired();

        return DB::transaction(function () {
            $this->lock();
            $raw = $this->raw();

            if ($raw['status'] === 'spinning') {
                return $this->snapshot();   // sudah berputar: jangan mulai ulang
            }

            $this->assertEnoughParticipants();

            $duration = $this->duration();

            return $this->save(
                'spinning',
                $raw['seq'] + 1,
                [],
                $duration > 0 ? now()->getTimestampMs() + $duration * 1000 : null,
            );
        });
    }

    /**
     * Hentikan undian dan pilih pemenang. Memanggil stop() saat undian sudah berhenti (mis. baru saja
     * berhenti otomatis) tidak error, hasil yang ada dikembalikan.
     *
     * @return array{status: string, seq: int, winners: list<string>, eligible: int, won: int, duration: int, remaining_ms: int|null}
     *
     * @throws DomainException bila belum dimulai atau peserta yang belum menang kurang dari SLOTS
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

            $this->assertEnoughParticipants();   // peserta bisa berubah sejak Start

            $picked = Participant::eligible()->inRandomOrder()->limit(self::SLOTS)->get(['id', 'name']);

            // Tersimpan permanen di peserta, jadi statusnya tetap terlihat setelah putaran berikutnya.
            Participant::whereKey($picked->modelKeys())->update(['won_at' => now()]);

            return $this->save('stopped', $raw['seq'] + 1, $picked->pluck('name')->all(), null);
        });
    }

    /**
     * Hapus status pemenang semua peserta (mereka bisa diundi lagi) dan kosongkan layar TV.
     * Tidak boleh saat undian sedang berputar. Mengembalikan jumlah peserta yang direset.
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
            $this->save('idle', $raw['seq'] + 1, [], null);   // seq naik: TV mengosongkan slot

            return $count;
        });
    }

    /** Jumlah peserta yang sudah pernah menang. */
    public function wonCount(): int
    {
        return Participant::whereNotNull('won_at')->count();
    }

    /** Jumlah peserta yang belum pernah menang. */
    public function eligibleCount(): int
    {
        return Participant::eligible()->count();
    }

    /** Nama peserta yang boleh diundi; dipakai TV untuk animasi putar. */
    public function pool(): Collection
    {
        return Participant::eligible()->orderBy('id')->pluck('name');
    }

    private function assertEnoughParticipants(): void
    {
        $eligible = $this->eligibleCount();

        if ($eligible < self::SLOTS) {
            throw new DomainException('Minimal '.self::SLOTS.' peserta yang belum menang diperlukan untuk undian (tersisa '.$eligible.'). Tambah peserta terlebih dahulu.');
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
            DB::transaction(fn () => $this->save('idle', $raw['seq'] + 1, [], null));
        }
    }

    /** @return array{status: string, seq: int, winners: list<string>, ends_at_ms: int|null} */
    private function raw(): array
    {
        $raw = json_decode(Setting::get(self::KEY) ?? '', true);

        return [
            'status' => $raw['status'] ?? 'idle',
            'seq' => (int) ($raw['seq'] ?? 0),
            'winners' => array_values($raw['winners'] ?? []),
            'ends_at_ms' => isset($raw['ends_at_ms']) ? (int) $raw['ends_at_ms'] : null,
        ];
    }

    /** @return array{status: string, seq: int, winners: list<string>, eligible: int, won: int, duration: int, remaining_ms: int|null} */
    private function snapshot(): array
    {
        $raw = $this->raw();
        $spinning = $raw['status'] === 'spinning' && $raw['ends_at_ms'] !== null;

        return [
            'status' => $raw['status'],
            'seq' => $raw['seq'],
            'winners' => $raw['winners'],
            'eligible' => $this->eligibleCount(),
            'won' => $this->wonCount(),
            'duration' => $this->duration(),
            'remaining_ms' => $spinning ? max(0, $raw['ends_at_ms'] - now()->getTimestampMs()) : null,
        ];
    }

    /** Kunci baris status agar dua perintah bersamaan (mis. Stop dan berhenti otomatis) tidak memilih pemenang dua kali. */
    private function lock(): void
    {
        Setting::where('key', self::KEY)->lockForUpdate()->first();
    }

    /**
     * @param  list<string>  $winners
     * @return array{status: string, seq: int, winners: list<string>, eligible: int, won: int, duration: int, remaining_ms: int|null}
     */
    private function save(string $status, int $seq, array $winners, ?int $endsAtMs): array
    {
        Setting::put(self::KEY, json_encode([
            'status' => $status,
            'seq' => $seq,
            'winners' => $winners,
            'ends_at_ms' => $endsAtMs,
        ], JSON_UNESCAPED_UNICODE));

        return $this->snapshot();
    }
}
