<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\DoorprizeSpin;
use App\Services\ParticipantSpreadsheet;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DoorprizeController extends Controller
{
    public function index(DoorprizeSpin $spin): View
    {
        $state = $spin->state();

        return view('doorprize', [
            'participants' => Participant::orderBy('id')->get(),
            'categories' => Participant::CATEGORIES,
            'spin' => $state,
            'prizes' => $state['prizes'],
            'quotaSetting' => $state['quota_setting'],
            'quotaTotal' => $state['quota_total'],
            'eligibleCount' => $state['eligible'],
            'eligibleTotal' => $state['eligible_total'],
            'eligibleByCategory' => $state['eligible_by_category'],
            'minSlots' => DoorprizeSpin::MIN_SLOTS,
            'maxSlots' => DoorprizeSpin::MAX_SLOTS,
            'defaultSlots' => DoorprizeSpin::DEFAULT_SLOTS,
            'slots' => $state['slots'],
            'maxDuration' => DoorprizeSpin::MAX_DURATION,
        ]);
    }

    /** Halaman spin untuk layar TV; status undian selanjutnya dibaca lewat polling tvState(). */
    public function tv(DoorprizeSpin $spin): View
    {
        $state = $spin->state();

        return view('doorprize-tv', [
            'participants' => $spin->pool(),
            'spin' => $state,
            'slots' => $state['slots'],
            'categories' => Participant::CATEGORIES,
        ]);
    }

    /**
     * Status undian untuk polling TV.
     */
    public function tvState(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $state = $spin->state();

        if ($request->has('seq') && (int) $request->query('seq') !== $state['seq'] && $state['status'] === 'spinning') {
            $state['pool'] = $spin->pool($state['categories'] ?? null);
        }

        return response()->json($state);
    }

    /** Simpan formulir SETTING LUCKY DRAW (kuota pemenang per kategori) */
    public function saveSetting(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'quotas' => ['required', 'array'],
            'quotas.*' => ['integer', 'min:0', 'max:'.DoorprizeSpin::MAX_SLOTS],
        ]);

        return $this->command(fn () => $spin->saveQuotaSetting($data['quotas']));
    }

    /** Mulai putaran undian */
    public function start(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', 'string', 'in:quota,category'],
            'slots' => ['nullable', 'integer', 'min:'.DoorprizeSpin::MIN_SLOTS, 'max:'.DoorprizeSpin::MAX_SLOTS],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],
            'quotas' => ['nullable', 'array'],
            'quotas.*' => ['integer', 'min:0', 'max:'.DoorprizeSpin::MAX_SLOTS],
            'prize_id' => ['nullable', 'integer'],
            'allocation' => ['nullable', 'array', 'max:'.DoorprizeSpin::MAX_SLOTS],
            'allocation.*.prize_id' => ['required', 'integer'],
            'allocation.*.count' => ['required', 'integer', 'min:1', 'max:'.DoorprizeSpin::MAX_SLOTS],
        ]);

        $mode = $data['mode'] ?? null;
        $slots = isset($data['slots']) ? (int) $data['slots'] : null;
        $categories = isset($data['categories']) ? (array) $data['categories'] : null;
        $quotas = isset($data['quotas']) ? (array) $data['quotas'] : null;
        $prizeId = isset($data['prize_id']) ? (int) $data['prize_id'] : null;
        $allocation = isset($data['allocation']) ? array_values($data['allocation']) : null;

        return $this->command(fn () => $spin->start($slots, $categories, $mode, $quotas, $prizeId, $allocation));
    }

    public function stop(DoorprizeSpin $spin): JsonResponse
    {
        return $this->command(fn () => $spin->stop());
    }

    /** Hapus status pemenang semua peserta */
    public function reset(DoorprizeSpin $spin): JsonResponse
    {
        return $this->command(fn () => ['reset' => $spin->reset()] + $spin->state());
    }

    /** Atur lama spin otomatis (detik) */
    public function duration(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'duration' => ['required', 'integer', 'min:0', 'max:'.DoorprizeSpin::MAX_DURATION],
        ], [
            'duration.required' => 'Durasi wajib diisi.',
            'duration.integer' => 'Durasi harus berupa angka bulat (detik).',
            'duration.min' => 'Durasi minimal 0 detik.',
            'duration.max' => 'Durasi maksimal '.DoorprizeSpin::MAX_DURATION.' detik.',
        ]);

        $spin->setDuration((int) $data['duration']);

        return response()->json($spin->state());
    }

    public function template(Request $request, ParticipantSpreadsheet $spreadsheet): BinaryFileResponse
    {
        $category = $request->query('category');

        return response()
            ->download($spreadsheet->template($category), 'template-peserta-doorprize.xlsx')
            ->deleteFileAfterSend();
    }

    /** Unduh berkas Excel daftar semua pemenang doorprize beserta hadiahnya */
    public function exportWinners(ParticipantSpreadsheet $spreadsheet): BinaryFileResponse
    {
        return response()
            ->download($spreadsheet->exportWinners(), 'daftar-pemenang-doorprize.xlsx')
            ->deleteFileAfterSend();
    }

    /** Ubah satu kolom peserta langsung dari tabel (nama, kategori, atau status pemenang). */
    public function update(Request $request, Participant $participant): JsonResponse
    {
        $data = $request->validate([
            'field' => ['required', 'in:name,category,status'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);
        $value = (string) ($data['value'] ?? '');

        switch ($data['field']) {
            case 'name':
                $value = trim(preg_replace('/\s+/u', ' ', $value));
                if ($value === '') {
                    return response()->json(['message' => 'Nama peserta wajib diisi.'], 422);
                }
                if (Participant::nameTaken($value, $participant->id)) {
                    return response()->json(['message' => 'Peserta dengan nama ini sudah ada.'], 422);
                }
                $participant->name = $value;
                break;
            case 'category':
                if (! in_array($value, Participant::CATEGORIES, true)) {
                    return response()->json(['message' => 'Kategori tidak valid.'], 422);
                }
                $participant->category = $value;
                break;
            default:
                if (! in_array($value, ['', 'PEMENANG'], true)) {
                    return response()->json(['message' => 'Status tidak valid.'], 422);
                }
                if ($value === 'PEMENANG') {
                    $participant->won_at ??= now();
                } else {
                    $participant->won_at = null;
                }
        }

        $participant->save();

        return response()->json([
            'id' => $participant->id,
            'name' => $participant->name,
            'category' => $participant->category,
            'status' => $participant->isWinner() ? 'PEMENANG' : '',
            'won_at_title' => $participant->won_at?->translatedFormat('d M Y H:i'),
            'won_at' => $participant->won_at?->toIso8601String(),
            'won_at_human' => $participant->won_at?->diffForHumans(),
            'won_at_formatted' => $participant->won_at?->translatedFormat('d M Y, H:i'),
        ]);
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()->route('doorprize')->with('status', 'Peserta dihapus.');
    }

    private function command(callable $action): JsonResponse
    {
        try {
            return response()->json($action());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
