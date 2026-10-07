<div>
    @include('livewire.partials.toolbar', ['sort' => $sort, 'sortOptions' => $sortOptions])

    <div class="m-list">
        @forelse ($answers as $answer)
            <div class="m-card" wire:key="answer-{{ $answer->id }}">
                <span class="m-pill">#{{ $numbers[$answer->id] + 1 }}</span>
                <div class="m-title">{{ $answer->answer }}</div>
                <div class="m-foot">
                    <span class="m-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 21l8 0" /><path d="M12 17l0 4" /><path d="M7 4l10 0" /><path d="M17 4v8a5 5 0 0 1 -10 0v-8" /><path d="M5 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /></svg>
                        Ranking {{ $answer->ranking }}
                    </span>
                    <div class="m-actions">
                        @if ($onTvIds->contains($answer->id))
                            <button type="button"
                                    class="btn m-mark {{ $answer->is_answered ? 'btn-orange' : 'btn-success' }}"
                                    data-answered-toggle="{{ route('family-100.answers.answered', $answer) }}">{{ $answer->is_answered ? 'Batalkan' : 'Terjawab' }}</button>
                        @else
                            <button type="button" class="btn btn-success m-mark" disabled title="Di luar batas {{ $question->display_limit }} jawaban yang tampil di TV">Terjawab</button>
                        @endif
                        <x-row-actions :delete-url="route('family-100.answers.destroy', $answer)" delete-message="Hapus jawaban ini?">
                            <button type="button" class="dropdown-item" data-answer-form="{{ $answer->id }}">Edit</button>
                        </x-row-actions>
                    </div>
                </div>
            </div>
        @empty
            <div class="m-empty" wire:key="empty">{{ $search !== '' ? 'Tidak ada jawaban yang cocok.' : 'Belum ada jawaban.' }}</div>
        @endforelse

        @include('livewire.partials.footer', ['hasMore' => $hasMore, 'shown' => $answers->count(), 'total' => $total, 'loaded' => $loaded])
    </div>
</div>
