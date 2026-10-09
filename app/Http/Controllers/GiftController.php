<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GiftController extends Controller
{
    /**
     * Dashboard Kelola Hadiah (Operator).
     */
    public function index(): View
    {
        $giftCount = Setting::giftCount();
        Gift::ensureCount($giftCount);

        $gifts = Gift::where('number', '<=', $giftCount)
            ->orderBy('number')
            ->get();

        $tvMode = Setting::tvMode();

        return view('family-100.gifts.index', compact('gifts', 'giftCount', 'tvMode'));
    }

    /**
     * Update data hadiah (nama/deskripsi/pemenang).
     */
    public function update(Request $request, Gift $gift): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'winner_name' => 'nullable|string|max:255',
        ]);

        $gift->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'gift' => $gift,
                'message' => "Hadiah #{$gift->number} berhasil diperbarui.",
            ]);
        }

        return back()->with('status', "Hadiah #{$gift->number} berhasil diperbarui.");
    }

    /**
     * Update data banyak hadiah sekaligus (bulk edit list).
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'gifts' => ['required', 'array'],
            'gifts.*.id' => ['required', 'integer', 'exists:gifts,id'],
            'gifts.*.name' => ['required', 'string', 'max:255'],
            'gifts.*.description' => ['nullable', 'string', 'max:1000'],
        ], [
            'gifts.required' => 'Data hadiah tidak boleh kosong.',
            'gifts.*.name.required' => 'Nama hadiah wajib diisi.',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['gifts'] as $item) {
                Gift::where('id', $item['id'])->update([
                    'name' => trim($item['name']),
                    'description' => isset($item['description']) && trim($item['description']) !== '' ? trim($item['description']) : null,
                ]);
            }
        });

        $count = count($validated['gifts']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => "Berhasil memperbarui {$count} data hadiah sekaligus.",
            ]);
        }

        return back()->with('status', "Berhasil memperbarui {$count} data hadiah sekaligus.");
    }

    /**
     * Acak (shuffle) nomor/posisi seluruh hadiah yang aktif.
     */
    public function shuffle(): JsonResponse|RedirectResponse
    {
        $giftCount = Setting::giftCount();
        $gifts = Gift::where('number', '<=', $giftCount)->get();

        if ($gifts->count() > 1) {
            $payloads = $gifts->map(fn ($g) => [
                'name' => $g->name,
                'description' => $g->description,
            ])->shuffle()->values();

            DB::transaction(function () use ($gifts, $payloads) {
                foreach ($gifts as $index => $gift) {
                    $gift->update([
                        'name' => $payloads[$index]['name'],
                        'description' => $payloads[$index]['description'],
                    ]);
                }
            });
        }

        if (request()->wantsJson()) {
            $refreshedGifts = Gift::where('number', '<=', $giftCount)->orderBy('number')->get()->map(fn ($g) => [
                'id' => $g->id,
                'number' => $g->number,
                'name' => $g->name,
                'description' => $g->description,
                'is_opened' => (bool) $g->is_opened,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Posisi seluruh hadiah berhasil diacak.',
                'gifts' => $refreshedGifts,
            ]);
        }

        return back()->with('status', 'Posisi seluruh hadiah berhasil diacak.');
    }

    /**
     * Buka / tutup sebuah kotak hadiah (toggle).
     */
    public function toggle(Gift $gift): JsonResponse
    {
        $isOpened = ! $gift->is_opened;
        $gift->update([
            'is_opened' => $isOpened,
            'opened_at' => $isOpened ? now() : null,
        ]);

        return response()->json([
            'status' => 'ok',
            'gift_id' => $gift->id,
            'number' => $gift->number,
            'is_opened' => $gift->is_opened,
            'name' => $gift->name,
            'description' => $gift->description,
        ]);
    }

    /**
     * Buka kotak hadiah tertentu secara eksplisit.
     */
    public function open(Gift $gift): JsonResponse
    {
        if (! $gift->is_opened) {
            $gift->update([
                'is_opened' => true,
                'opened_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'gift_id' => $gift->id,
            'number' => $gift->number,
            'is_opened' => true,
            'name' => $gift->name,
            'description' => $gift->description,
        ]);
    }

    /**
     * Reset semua kotak hadiah menjadi tertutup kembali.
     */
    public function reset(): JsonResponse|RedirectResponse
    {
        Gift::resetAll();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Semua kotak hadiah berhasil ditutup kembali.',
            ]);
        }

        return back()->with('status', 'Semua kotak hadiah berhasil ditutup kembali.');
    }

    /**
     * Ubah jumlah total kotak hadiah yang aktif (1 s/d 20, default 15).
     */
    public function updateCount(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'gift_count' => 'required|integer|min:1|max:20',
        ]);

        $count = (int) $data['gift_count'];
        Setting::put(Setting::GIFT_COUNT, $count);
        Gift::ensureCount($count);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'gift_count' => $count,
                'message' => "Jumlah kotak hadiah diubah menjadi {$count} kotak.",
            ]);
        }

        return back()->with('status', "Jumlah kotak hadiah diubah menjadi {$count} kotak.");
    }

    /**
     * Beralih mode layar TV panggung (Kuis vs Hadiah).
     */
    public function switchTvMode(Request $request): JsonResponse|RedirectResponse
    {
        $mode = $request->input('mode');
        if (! in_array($mode, [Setting::TV_MODE_QUIZ, Setting::TV_MODE_GIFT], true)) {
            $mode = Setting::tvMode() === Setting::TV_MODE_QUIZ ? Setting::TV_MODE_GIFT : Setting::TV_MODE_QUIZ;
        }

        Setting::put(Setting::TV_MODE, $mode);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'tv_mode' => $mode,
                'message' => $mode === Setting::TV_MODE_GIFT
                    ? 'Layar TV panggung beralih ke Mode Hadiah.'
                    : 'Layar TV panggung beralih ke Mode Kuis.',
            ]);
        }

        return back()->with('status', $mode === Setting::TV_MODE_GIFT
            ? 'Layar TV panggung beralih ke Mode Hadiah.'
            : 'Layar TV panggung beralih ke Mode Kuis.'
        );
    }

    /**
     * Tampilan layar TV Hadiah panggung (Kotak Kado 3D Emas).
     */
    public function tv(): View
    {
        Setting::put(Setting::TV_MODE, Setting::TV_MODE_GIFT);

        $giftCount = Setting::giftCount();
        Gift::ensureCount($giftCount);

        $gifts = Gift::where('number', '<=', $giftCount)
            ->orderBy('number')
            ->get();

        return view('family-100.gifts.tv', compact('gifts', 'giftCount'));
    }

    /**
     * Endpoint JSON polling state untuk TV Hadiah.
     */
    public function tvState(): JsonResponse
    {
        $giftCount = Setting::giftCount();
        $gifts = Gift::where('number', '<=', $giftCount)
            ->orderBy('number')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'number' => $g->number,
                'name' => $g->name,
                'description' => $g->description,
                'is_opened' => (bool) $g->is_opened,
                'winner_name' => $g->winner_name,
                'opened_at' => $g->opened_at?->toIso8601String(),
            ]);

        return response()->json([
            'tv_mode' => Setting::tvMode(),
            'gift_count' => $giftCount,
            'gifts' => $gifts,
            'opened_count' => $gifts->where('is_opened', true)->count(),
        ]);
    }
}
