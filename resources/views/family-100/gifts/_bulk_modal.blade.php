<!-- Modal Edit Semua Hadiah Sekaligus (Bulk List) -->
<div class="modal modal-blur fade" id="modalBulkEditGifts" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <form id="formBulkEditGifts" method="POST" action="{{ route('family-100.gifts.bulk') }}" class="modal-content shadow-lg border-0">
            @csrf
            <div class="modal-header bg-primary text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-white text-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M9 4h10" /><path d="M14 8h5" /></svg>
                    </span>
                    <div>
                        <h4 class="modal-title fw-bold m-0 text-white">Edit Semua Hadiah Sekaligus</h4>
                        <div class="text-white-50 small">Atur nama dan deskripsi untuk {{ $giftCount }} kotak hadiah dalam satu formulir</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="p-3 bg-body-tertiary border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-secondary small d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary flex-none" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9h.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
                        <span>Isi nama hadiah langsung secara berurutan. Klik <strong>Simpan Semua Hadiah</strong> untuk menyimpan sekaligus.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm d-inline-flex align-items-center gap-1" id="btnShuffleBulkInputs" title="Acak posisi atau urutan nomor hadiah">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 4l3 3l-3 3" /><path d="M18 20l3 -3l-3 -3" /><path d="M3 7h3a5 5 0 0 1 5 5a5 5 0 0 0 5 5h5" /><path d="M21 7h-5a4.978 4.978 0 0 0 -3 1.018m-4.004 7.964a4.978 4.978 0 0 1 -2.996 1.018h-3" /></svg>
                            <span>Acak Nomor Hadiah</span>
                        </button>
                        <span class="badge bg-blue-lt fw-bold px-2 py-1">
                            Total: {{ $gifts->count() }} Kotak Hadiah
                        </span>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 58vh;">
                    <table class="table table-vcenter table-hover table-striped m-0">
                        <thead class="sticky-top bg-body" style="z-index: 10;">
                            <tr>
                                <th style="width: 80px;" class="text-center py-2">Kotak</th>
                                <th style="min-width: 250px;" class="py-2">Nama / Isi Hadiah <span class="text-danger">*</span></th>
                                <th style="min-width: 230px;" class="py-2">Keterangan / Catatan (Opsional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gifts as $index => $gift)
                                <tr>
                                    <td class="text-center align-middle py-2">
                                        <input type="hidden" name="gifts[{{ $index }}][id]" value="{{ $gift->id }}">
                                        <span class="badge bg-warning text-dark fs-3 fw-bold px-2 py-1 shadow-sm">
                                            #{{ $gift->number }}
                                        </span>
                                    </td>
                                    <td class="align-middle py-2">
                                        <input type="text"
                                               name="gifts[{{ $index }}][name]"
                                               class="form-control form-control-sm fw-semibold bulk-gift-input-name"
                                               data-bulk-id="{{ $gift->id }}"
                                               value="{{ $gift->name }}"
                                               placeholder="Contoh: Kipas Angin / Hadiah #{{ $gift->number }}"
                                               required>
                                    </td>
                                    <td class="align-middle py-2">
                                        <input type="text"
                                               name="gifts[{{ $index }}][description]"
                                               class="form-control form-control-sm text-secondary bulk-gift-input-desc"
                                               data-bulk-id="{{ $gift->id }}"
                                               value="{{ $gift->description }}"
                                               placeholder="Contoh: Merk Philips / Dari sponsor">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 px-3 bg-body-tertiary d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-warning text-dark fw-bold d-inline-flex align-items-center gap-2 shadow-sm" id="btnShuffleBulkInputsFooter" title="Acak posisi atau urutan nomor hadiah">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 4l3 3l-3 3" /><path d="M18 20l3 -3l-3 -3" /><path d="M3 7h3a5 5 0 0 1 5 5a5 5 0 0 0 5 5h5" /><path d="M21 7h-5a4.978 4.978 0 0 0 -3 1.018m-4.004 7.964a4.978 4.978 0 0 1 -2.996 1.018h-3" /></svg>
                    <span>Acak Nomor Hadiah</span>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="btnSubmitBulkGifts">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                        <span>Simpan Semua Hadiah</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalBulk = document.getElementById('modalBulkEditGifts');
    if (!modalBulk) return;

    // Sinkronisasi data saat modal dibuka
    modalBulk.addEventListener('show.bs.modal', () => {
        document.querySelectorAll('[data-gift-card]').forEach(cardCol => {
            const id = cardCol.dataset.giftCard;
            const editBtn = cardCol.querySelector('[data-bs-target="#modalEditGift"]');
            if (id && editBtn) {
                const nameInput = modalBulk.querySelector(`.bulk-gift-input-name[data-bulk-id="${id}"]`);
                const descInput = modalBulk.querySelector(`.bulk-gift-input-desc[data-bulk-id="${id}"]`);
                if (nameInput && editBtn.dataset.name !== undefined) nameInput.value = editBtn.dataset.name;
                if (descInput && editBtn.dataset.description !== undefined) descInput.value = editBtn.dataset.description;
            }
        });
    });

    // Fungsi Mengacak Nomor / Urutan Hadiah di dalam modal
    const performBulkShuffle = () => {
        const nameInputs = Array.from(modalBulk.querySelectorAll('.bulk-gift-input-name'));
        const descInputs = Array.from(modalBulk.querySelectorAll('.bulk-gift-input-desc'));

        if (nameInputs.length < 2) return;

        // Ambil pasangan nama & deskripsi saat ini
        const items = nameInputs.map((nInput, idx) => ({
            name: nInput.value,
            desc: descInputs[idx] ? descInputs[idx].value : ''
        }));

        // Pastikan ada input yang terisi
        const hasContent = items.some(item => item.name.trim() !== '');
        if (!hasContent) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Data Masih Kosong',
                    text: 'Silakan isi nama hadiah terlebih dahulu sebelum mengacak posisi nomor.',
                    confirmButtonColor: '#206bc4'
                });
            }
            return;
        }

        // Fisher-Yates Shuffle
        for (let i = items.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [items[i], items[j]] = [items[j], items[i]];
        }

        // Kembalikan ke input form dengan efek visual pulse / highlight
        items.forEach((item, idx) => {
            if (nameInputs[idx]) {
                nameInputs[idx].value = item.name;
                nameInputs[idx].style.transition = 'all 0.35s ease';
                nameInputs[idx].style.backgroundColor = '#fef08a';
                nameInputs[idx].style.borderColor = '#eab308';
                setTimeout(() => {
                    nameInputs[idx].style.backgroundColor = '';
                    nameInputs[idx].style.borderColor = '';
                }, 750);
            }
            if (descInputs[idx]) {
                descInputs[idx].value = item.desc;
                descInputs[idx].style.transition = 'all 0.35s ease';
                descInputs[idx].style.backgroundColor = '#fef08a';
                descInputs[idx].style.borderColor = '#eab308';
                setTimeout(() => {
                    descInputs[idx].style.backgroundColor = '';
                    descInputs[idx].style.borderColor = '';
                }, 750);
            }
        });

        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Nomor hadiah berhasil diacak!',
                timer: 2000,
                showConfirmButton: false
            });
        }
    };

    const btnShuffleTop = document.getElementById('btnShuffleBulkInputs');
    if (btnShuffleTop) {
        btnShuffleTop.addEventListener('click', performBulkShuffle);
    }

    const btnShuffleBottom = document.getElementById('btnShuffleBulkInputsFooter');
    if (btnShuffleBottom) {
        btnShuffleBottom.addEventListener('click', performBulkShuffle);
    }
});
</script>

