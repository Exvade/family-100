// Modal tambah/edit jawaban (komponen Livewire AnswerForm).
// Tombol apa pun dengan data-answer-form membukanya: nilai kosong = tambah, berisi id = edit jawaban itu.
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-answer-form]');
    if (!trigger) {
        return;
    }

    const id = trigger.dataset.answerForm;
    window.Livewire.dispatch('open-answer-form', { id: id ? Number(id) : null });
});

// Server selesai mengisi form -> tampilkan modal.
window.addEventListener('answer-form-ready', () => {
    globalThis.bootstrap.Modal.getOrCreateInstance(document.getElementById('answer-modal')).show();
});

document.addEventListener('shown.bs.modal', (event) => {
    if (event.target.id === 'answer-modal') {
        event.target.querySelector('#answer-input')?.focus();
    }
});
