<div>
    @include('livewire.partials.toolbar', ['sort' => $sort, 'sortOptions' => $sortOptions])

    <div class="m-list">
        @forelse ($questions as $question)
            <div class="m-card" wire:key="question-{{ $question->id }}">
                <span class="m-pill">#{{ $numbers[$question->id] + 1 }}</span>
                <div class="m-title">{{ $question->question }}</div>
                <div class="m-foot">
                    <span class="m-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l11 0" /><path d="M9 12l11 0" /><path d="M9 18l11 0" /><path d="M5 6l0 .01" /><path d="M5 12l0 .01" /><path d="M5 18l0 .01" /></svg>
                        {{ $question->answers_count }} jawaban
                    </span>
                    <span class="m-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                        {{ $question->display_limit }} di TV
                    </span>
                    <div class="m-actions">
                        <x-row-actions :delete-url="route('family-100.questions.destroy', $question)" delete-message="Hapus pertanyaan ini beserta semua jawabannya?">
                            <a class="dropdown-item" href="{{ route('family-100.answers.index', $question) }}">Kelola Jawaban</a>
                            <a class="dropdown-item" href="{{ route('family-100.questions.tv', $question) }}" target="_blank" rel="noopener">Tampil di TV</a>
                            <a class="dropdown-item" href="{{ route('family-100.questions.edit', $question) }}">Edit</a>
                        </x-row-actions>
                    </div>
                </div>
            </div>
        @empty
            <div class="m-empty" wire:key="empty">{{ $search !== '' ? 'Tidak ada pertanyaan yang cocok.' : 'Belum ada pertanyaan.' }}</div>
        @endforelse

        @include('livewire.partials.footer', ['hasMore' => $hasMore, 'shown' => $questions->count(), 'total' => $total, 'loaded' => $loaded])
    </div>
</div>
