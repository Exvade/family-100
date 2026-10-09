<div>
    @include('livewire.partials.toolbar', ['sort' => $sort, 'sortOptions' => $sortOptions])

    <div class="m-list">
        @forelse ($participants as $participant)
            <div class="m-card" wire:key="card-{{ $participant->id }}">
                <span class="m-pill">#{{ $numbers[$participant->id] + 1 }}</span>
                <span class="badge bg-blue-lt ms-1 align-middle">{{ \App\Models\Participant::displayLabel($participant->category) }}</span>
                @if ($participant->isWinner())
                    <span class="badge bg-green-lt ms-1 align-middle">PEMENANG</span>
                @endif
                <div class="m-title">{{ $participant->name }}</div>
                <div class="m-foot">
                    <span class="m-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 10h.01" /><path d="M15 10h.01" /><path d="M9.5 15a3.5 3.5 0 0 0 5 0" /></svg>
                        Peserta undian
                    </span>
                    <div class="m-actions">
                        <x-row-actions :delete-url="route('doorprize.participants.destroy', $participant)" delete-message="Hapus peserta ini?">
                            <button type="button" class="dropdown-item" data-participant-form="{{ $participant->id }}">Edit</button>
                        </x-row-actions>
                    </div>
                </div>
            </div>
        @empty
            <div class="m-empty" wire:key="card-empty">{{ $search !== '' ? 'Tidak ada peserta yang cocok.' : 'Belum ada peserta. Tambah manual atau unggah file Excel.' }}</div>
        @endforelse
    </div>

    @include('livewire.partials.footer', ['hasMore' => $hasMore, 'shown' => $participants->count(), 'total' => $total, 'loaded' => $loaded])
</div>
