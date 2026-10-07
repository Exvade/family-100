@csrf

@php
    $initialAnswers = [];
    if (old('answers')) {
        $initialAnswers = array_values(old('answers'));
    } elseif (isset($question) && $question->relationLoaded('answers') && $question->answers->isNotEmpty()) {
        $initialAnswers = $question->answers->map(fn($a) => [
            'id' => $a->id,
            'ranking' => $a->ranking,
            'answer' => $a->answer,
        ])->values()->toArray();
    }
@endphp

<div class="mb-3">
    <label class="form-label required" for="question">Pertanyaan</label>
    <input type="text" id="question" name="question" class="form-control @error('question') is-invalid @enderror"
           value="{{ old('question', $question->question ?? '') }}" placeholder="Contoh: Sebutkan benda yang biasa dibawa saat kondangan" required autofocus>
    @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label class="form-label required" for="display_limit">Jumlah Jawaban (Tampil di TV)</label>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <input type="number" id="display_limit" name="display_limit" class="form-control @error('display_limit') is-invalid @enderror"
               value="{{ old('display_limit', $question->display_limit ?? 5) }}" min="1" max="20" required style="width: 85px;">
        <span class="text-secondary small me-2">jawaban</span>

        <!-- Tombol Cepat Preset -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Preset jumlah jawaban">
            <button type="button" class="btn btn-outline-secondary" data-set-limit="4">4</button>
            <button type="button" class="btn btn-outline-secondary" data-set-limit="5">5</button>
            <button type="button" class="btn btn-outline-secondary" data-set-limit="6">6</button>
            <button type="button" class="btn btn-outline-secondary" data-set-limit="8">8</button>
            <button type="button" class="btn btn-outline-secondary" data-set-limit="10">10</button>
        </div>
    </div>
    @error('display_limit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <small class="form-hint">Kolom jawaban di bawah akan otomatis menyesuaikan dengan angka jumlah jawaban di atas.</small>
</div>

<div class="hr-text text-uppercase text-secondary fw-bold my-4">
    Daftar Jawaban (Urutan Ranking)
</div>

<div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 flex-none" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
    <div>
        Isi jawaban mulai dari <strong>Ranking #1 (jawaban paling populer/poin terbesar)</strong> secara berurutan ke bawah.
    </div>
</div>

<div id="answers-container" class="d-flex flex-column gap-2 mb-4">
    <!-- Di-generate otomatis oleh script sesuai jumlah jawaban -->
</div>

<div class="card-footer form-actions px-0 pb-0">
    <a href="{{ route('family-100.questions.index') }}" class="btn btn-link">Batal</a>
    <button type="submit" class="btn btn-primary px-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
        Simpan Pertanyaan & Jawaban
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const limitInput = document.getElementById('display_limit');
    const container = document.getElementById('answers-container');
    const presetButtons = document.querySelectorAll('[data-set-limit]');

    const initialAnswers = @json($initialAnswers);

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderAnswerRows() {
        const count = Math.max(1, Math.min(20, parseInt(limitInput.value, 10) || 5));

        // Tangkap isi input saat ini sebelum me-render ulang agar teks yang sudah diketik tidak hilang
        const currentValues = [];
        container.querySelectorAll('[data-answer-row]').forEach((row, idx) => {
            currentValues[idx] = {
                id: row.querySelector('input[data-field="id"]')?.value || '',
                ranking: idx + 1,
                answer: row.querySelector('input[data-field="answer"]')?.value || '',
            };
        });

        container.innerHTML = '';

        for (let i = 0; i < count; i++) {
            const ranking = i + 1;
            const existing = currentValues[i] || initialAnswers[i] || { id: '', ranking: ranking, answer: '' };

            const row = document.createElement('div');
            row.className = 'input-group';
            row.setAttribute('data-answer-row', ranking);

            const placeholder = ranking === 1 
                ? 'Jawaban ranking #1 (paling banyak dijawab / poin tertinggi)...' 
                : `Jawaban ranking #${ranking}...`;

            row.innerHTML = `
                <span class="input-group-text fw-bold bg-primary text-white" style="min-width: 54px; justify-content: center;">
                    #${ranking}
                </span>
                <input type="hidden" name="answers[${i}][id]" data-field="id" value="${escapeHtml(existing.id)}">
                <input type="hidden" name="answers[${i}][ranking]" data-field="ranking" value="${ranking}">
                <input type="text" 
                       name="answers[${i}][answer]" 
                       data-field="answer"
                       class="form-control" 
                       placeholder="${placeholder}" 
                       value="${escapeHtml(existing.answer)}" 
                       required>
            `;

            container.appendChild(row);
        }

        // Update active style on preset buttons
        presetButtons.forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.setLimit, 10) === count);
        });
    }

    limitInput.addEventListener('input', renderAnswerRows);

    presetButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            limitInput.value = btn.dataset.setLimit;
            renderAnswerRows();
        });
    });

    renderAnswerRows();
});
</script>
