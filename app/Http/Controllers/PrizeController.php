<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Services\DoorprizeSpin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/** Daftar hadiah doorprize; setiap respons berisi status undian terbaru (termasuk daftar hadiah). */
class PrizeController extends Controller
{
    public function store(Request $request, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.Prize::MAX_QUANTITY],
            'image' => $this->imageRules(),
        ], $this->messages());

        $prize = Prize::create([
            'name' => $this->cleanName($data['name']),
            'quantity' => (int) $data['quantity'],
        ]);

        if ($request->hasFile('image')) {
            $this->storeImage($prize, $request->file('image'));
        }

        return response()->json($spin->state());
    }

    /** Ubah nama atau jumlah hadiah langsung dari tabel. */
    public function update(Request $request, Prize $prize, DoorprizeSpin $spin): JsonResponse
    {
        $data = $request->validate([
            'field' => ['required', 'in:name,quantity'],
            'value' => ['required', 'string', 'max:255'],
        ], $this->messages());

        if ($data['field'] === 'name') {
            $name = $this->cleanName($data['value']);
            if ($name === '') {
                return response()->json(['message' => 'Nama hadiah wajib diisi.'], 422);
            }
            $prize->name = $name;
        } else {
            if (! ctype_digit($data['value']) || (int) $data['value'] < 1 || (int) $data['value'] > Prize::MAX_QUANTITY) {
                return response()->json(['message' => 'Jumlah hadiah harus angka 1 sampai '.Prize::MAX_QUANTITY.'.'], 422);
            }

            $awarded = $prize->winners()->count();
            if ((int) $data['value'] < $awarded) {
                return response()->json(['message' => "Jumlah tidak boleh kurang dari hadiah yang sudah dimenangkan ({$awarded})."], 422);
            }
            $prize->quantity = (int) $data['value'];
        }

        $prize->save();

        return response()->json($spin->state());
    }

    /** Unggah atau ganti gambar hadiah. */
    public function uploadImage(Request $request, Prize $prize, DoorprizeSpin $spin): JsonResponse
    {
        $request->validate(['image' => array_merge(['required'], $this->imageRules())], $this->messages());

        $this->storeImage($prize, $request->file('image'));

        return response()->json($spin->state());
    }

    public function removeImage(Prize $prize, DoorprizeSpin $spin): JsonResponse
    {
        $prize->forgetImage();
        $prize->save();

        return response()->json($spin->state());
    }

    public function destroy(Prize $prize, DoorprizeSpin $spin): JsonResponse
    {
        $prize->forgetImage();
        $prize->delete();

        return response()->json($spin->state());
    }

    /** @return list<string> */
    private function imageRules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    /** Simpan gambar baru dan hapus gambar lamanya. */
    private function storeImage(Prize $prize, UploadedFile $file): void
    {
        $prize->forgetImage();
        $prize->image_path = $file->store(Prize::IMAGE_DIR, 'public');
        $prize->save();
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Nama hadiah wajib diisi.',
            'name.max' => 'Nama hadiah maksimal 255 karakter.',
            'quantity.required' => 'Jumlah hadiah wajib diisi.',
            'quantity.integer' => 'Jumlah hadiah harus berupa angka bulat.',
            'quantity.min' => 'Jumlah hadiah minimal 1.',
            'quantity.max' => 'Jumlah hadiah maksimal '.Prize::MAX_QUANTITY.'.',
            'image.image' => 'Berkas harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Pastikan ukurannya maksimal 2 MB.',
            'image.required' => 'Pilih gambar terlebih dahulu.',
        ];
    }
}
