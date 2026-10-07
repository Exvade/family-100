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
            'participants' => $spin->pool(),   // hanya yang belum menang (sesuai filter kategori aktif bila ada)
            'spin' => $state,
            'slots' => $state['slots'],
            'categories' => Participant::CATEGORIES,
        ]);
    }

    /**
     * Status undian. Daftar nama untuk animasi putar hanya ikut dikirim ke TV (yang mengirim ?seq=)
     * saat ada putaran baru yang belum dikenalnya, supaya polling tiap detik tetap ringan.
     */
    public function tvState(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $state = $spin->state();

        if ($request->has('seq') && (int) $request->query('seq') !== $state['seq'] && $state['status'] === 'spinning') {
            $state['pool'] = $spin->pool($state['categories'] ?? null);
        }

        return response()->json($state);
    }

    public function start(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'slots' => ['nullable', 'integer', 'min:'.DoorprizeSpin::MIN_SLOTS, 'max:'.DoorprizeSpin::MAX_SLOTS],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],
        ]);

        $slots = isset($data['slots']) ? (int) $data['slots'] : null;
        $categories = isset($data['categories']) ? (array) $data['categories'] : null;

        return $this->command(fn () => $spin->start($slots, $categories));
    }

    public function configure(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'slots' => ['required', 'integer', 'min:'.DoorprizeSpin::MIN_SLOTS, 'max:'.DoorprizeSpin::MAX_SLOTS],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],
        ]);

        return response()->json($spin->configure((int) $data['slots'], $data['categories'] ?? []));
    }

    public function stop(DoorprizeSpin $spin): JsonResponse
    {
        return $this->command(fn () => $spin->stop());
    }

    /** Hapus status pemenang semua peserta; `reset` pada respons = jumlah peserta yang direset. */
    public function reset(DoorprizeSpin $spin): JsonResponse
    {
        return $this->command(fn () => ['reset' => $spin->reset()] + $spin->state());
    }

    /** Atur lama spin otomatis (detik); berlaku mulai undian berikutnya. */
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
