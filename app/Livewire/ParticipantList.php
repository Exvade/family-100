<?php

namespace App\Livewire;

use App\Livewire\Concerns\LoadsMore;
use App\Models\Participant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Daftar peserta untuk mobile (kartu + infinite scroll); desktop memakai x-datatable di halaman. */
class ParticipantList extends Component
{
    use LoadsMore;

    /** Dikirim dasbor setelah undian dihentikan agar status pemenang di daftar ikut diperbarui. */
    #[On('participants-changed')]
    public function refresh(): void {}

    public function render(): View
    {
        [$key, $dir] = $this->sortParts(['no', 'name', 'status']);

        $query = Participant::query();
        $this->applySearch($query, 'name');

        $total = (clone $query)->count();
        if ($key === 'status') {
            // desc = pemenang dulu, asc = yang belum menang dulu
            $query->orderByRaw('won_at is null '.($dir === 'desc' ? 'asc' : 'desc'))->orderBy('id');
        } else {
            $query->orderBy($key === 'name' ? 'name' : 'id', $dir);
            if ($key === 'name') {
                $query->orderBy('id', $dir);
            }
        }

        $rows = $query->limit($this->limit())->get();

        return view('livewire.participant-list', [
            'participants' => $rows,
            'total' => $total,
            'hasMore' => $total > $rows->count(),
            // Nomor mengikuti urutan input (id), tetap sama walau daftar dicari atau diurutkan.
            'numbers' => Participant::orderBy('id')->pluck('id')->flip(),
            'sortOptions' => [
                'no:asc' => 'Urutan input (terlama)',
                'no:desc' => 'Urutan input (terbaru)',
                'name:asc' => 'Nama (A–Z)',
                'name:desc' => 'Nama (Z–A)',
                'status:desc' => 'Pemenang dulu',
                'status:asc' => 'Belum menang dulu',
            ],
        ]);
    }
}
