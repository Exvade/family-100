// Modal peserta doorprize (komponen Livewire ParticipantForm & ParticipantImport).
// Tombol dengan data-participant-form membuka form: nilai kosong = tambah, berisi id = edit peserta itu.
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-participant-form]');
    if (!trigger) {
        return;
    }

    const id = trigger.dataset.participantForm;
    window.Livewire.dispatch('open-participant-form', { id: id ? Number(id) : null });
});

// Server selesai mengisi form -> tampilkan modal.
window.addEventListener('participant-form-ready', () => {
    globalThis.bootstrap.Modal.getOrCreateInstance(document.getElementById('participant-modal')).show();
});

document.addEventListener('shown.bs.modal', (event) => {
    if (event.target.id === 'participant-modal') {
        event.target.querySelector('#participant-name-input')?.focus();
    }
});

// Modal impor dibuka lewat data-bs-toggle; saat ditutup, kosongkan file dan pesan error sebelumnya.
document.addEventListener('hidden.bs.modal', (event) => {
    if (event.target.id === 'participant-import-modal') {
        window.Livewire.dispatch('reset-participant-import');
    }
});
