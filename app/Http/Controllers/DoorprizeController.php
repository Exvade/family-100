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
        return view('doorprize', [
            'participants' => Participant::orderBy('id')->get(),
            'spin' => $spin->state(),
            'eligibleCount' => $spin->eligibleCount(),
            'slots' => DoorprizeSpin::SLOTS,
            'maxDuration' => DoorprizeSpin::MAX_DURATION,
        ]);
    }

    /** Halaman spin untuk layar TV; status undian selanjutnya dibaca lewat polling tvState(). */
    public function tv(DoorprizeSpin $spin): View
    {
        return view('doorprize-tv', [
            'participants' => $spin->pool(),   // hanya yang belum menang
            'spin' => $spin->state(),
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
            $state['pool'] = $spin->pool();
        }

        return response()->json($state);
    }

    public function start(DoorprizeSpin $spin): JsonResponse
    {
        return $this->command(fn () => $spin->start());
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

    public function template(ParticipantSpreadsheet $spreadsheet): BinaryFileResponse
    {
        return response()
            ->download($spreadsheet->template(), 'template-peserta-doorprize.xlsx')
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
