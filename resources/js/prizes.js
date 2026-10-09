import Swal from 'sweetalert2';

// Kartu Daftar Hadiah di dasbor /doorprize: tambah, ubah langsung (klik sel nama/jumlah), dan hapus hadiah.
// Setiap respons server berisi status undian terbaru; diteruskan ke doorprize-control lewat event "doorprize:state".

const card = document.getElementById('prize-card');
const tbody = document.getElementById('prize-tbody');
const countBadge = document.getElementById('prize-count-badge');

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[m]));

const imageInput = document.getElementById('prize-image-input');
const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
let imageTargetId = null;
let lastPrizes = [];
let editing = false;

// Gambar hadiah bisa diklik untuk mengganti; tanpa gambar tampil kotak "Unggah".
function thumbnail(p) {
    if (!p.image_url) {
        return '<span class="prize-thumb-empty" data-prize-image-pick title="Klik untuk mengunggah gambar">Unggah</span>';
    }

    return `<img src="${escapeHtml(p.image_url)}" alt="${escapeHtml(p.name)}" class="rounded border prize-thumb" role="button" data-prize-image-pick title="Klik untuk mengganti gambar">
        <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1" data-prize-image-remove title="Hapus gambar">×</button>`;
}

let lastSignature = '';

export function renderPrizeList(prizes, force = false) {
    lastPrizes = prizes;
    if (!tbody || editing) return;

    // Polling tiap 3 detik: lewati bila data sama supaya gambar tidak dimuat ulang / berkedip.
    const signature = JSON.stringify(prizes);
    if (!force && signature === lastSignature) return;
    lastSignature = signature;

    if (countBadge) countBadge.textContent = `${prizes.length} Hadiah`;

    if (prizes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Belum ada hadiah. Tambahkan lewat form di atas.</td></tr>';
        return;
    }

    tbody.innerHTML = prizes.map((p, i) => {
        const done = p.remaining < 1;
        const winners = p.winners.length
            ? p.winners.map(escapeHtml).join(', ')
            : '<span class="text-muted">—</span>';

        return `
            <tr data-prize-id="${p.id}" data-name="${escapeHtml(p.name)}" data-quantity="${p.quantity}">
                <td class="text-secondary fw-bold">#${i + 1}</td>
                <td class="text-nowrap">${thumbnail(p)}</td>
                <td class="fw-bold text-dark inline-editable" data-prize-field="name" title="Klik untuk mengubah nama">${escapeHtml(p.name)}</td>
                <td class="inline-editable" data-prize-field="quantity" title="Klik untuk mengubah jumlah">${p.quantity}</td>
                <td class="text-nowrap">
                    <span class="badge bg-${done ? 'green' : 'azure'}-lt">${p.awarded} / ${p.quantity}</span>
                    <span class="text-secondary small ms-1">sisa ${p.remaining}</span>
                </td>
                <td class="small">${winners}</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-prize-delete>Hapus</button></td>
            </tr>`;
    }).join('');
}

async function call(method, url, body) {
    const isForm = body instanceof FormData;
    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() };
    if (!isForm) headers['Content-Type'] = 'application/json';

    const response = await fetch(url, {
        method,
        headers,
        body: body === undefined ? undefined : (isForm ? body : JSON.stringify(body)),
    });
    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message ?? `HTTP ${response.status}`));
    }

    return data;
}

function toast(icon, title) {
    Swal.fire({ toast: true, position: 'top-end', icon, title, timer: 2200, showConfirmButton: false });
}

function publish(state) {
    window.dispatchEvent(new CustomEvent('doorprize:state', { detail: state }));
}

if (card) {
    const itemUrl = (id) => `${card.dataset.itemUrl}/${id}`;

    // Form tambah hadiah ada di modal (di luar kartu).
    const form = document.querySelector('[data-prize-form]');
    const formError = form?.querySelector('[data-prize-form-error]');
    const modalEl = document.getElementById('prize-modal');

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        formError.hidden = true;

        const file = form.elements.image.files[0];
        if (file && file.size > MAX_IMAGE_BYTES) {
            formError.textContent = 'Ukuran gambar maksimal 2 MB.';
            formError.hidden = false;
            button.disabled = false;
            return;
        }

        try {
            publish(await call('POST', card.dataset.storeUrl, new FormData(form)));
            globalThis.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            toast('success', 'Hadiah ditambahkan');
        } catch (error) {
            formError.textContent = error.message;
            formError.hidden = false;
        } finally {
            button.disabled = false;
        }
    });

    modalEl?.addEventListener('shown.bs.modal', () => form.elements.name.focus());
    // Setiap kali modal ditutup, form dikosongkan agar pembukaan berikutnya bersih.
    modalEl?.addEventListener('hidden.bs.modal', () => {
        form.reset();
        formError.hidden = true;
    });

    tbody.addEventListener('click', async (event) => {
        const row = event.target.closest('tr[data-prize-id]');
        if (!row) return;

        const deleteBtn = event.target.closest('[data-prize-delete]');
        if (deleteBtn) {
            const confirmed = await Swal.fire({
                icon: 'warning',
                title: 'Hapus hadiah ini?',
                text: row.dataset.name,
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
            });
            if (!confirmed.isConfirmed) return;

            try {
                publish(await call('DELETE', itemUrl(row.dataset.prizeId)));
            } catch (error) {
                toast('error', error.message);
            }
            return;
        }

        if (event.target.closest('[data-prize-image-remove]')) {
            try {
                publish(await call('DELETE', `${itemUrl(row.dataset.prizeId)}/gambar`));
                toast('success', 'Gambar dihapus');
            } catch (error) {
                toast('error', error.message);
            }
            return;
        }

        if (event.target.closest('[data-prize-image-pick]')) {
            imageTargetId = row.dataset.prizeId;
            imageInput.value = '';
            imageInput.click();
            return;
        }

        const cell = event.target.closest('[data-prize-field]');
        if (cell && !cell.querySelector('input')) edit(row, cell);
    });

    imageInput?.addEventListener('change', async () => {
        const file = imageInput.files[0];
        if (!file || !imageTargetId) return;
        if (file.size > MAX_IMAGE_BYTES) {
            toast('error', 'Ukuran gambar maksimal 2 MB.');
            return;
        }

        const body = new FormData();
        body.append('image', file);

        try {
            publish(await call('POST', `${itemUrl(imageTargetId)}/gambar`, body));
            toast('success', 'Gambar diperbarui');
        } catch (error) {
            toast('error', error.message);
        }
    });
}

function edit(row, cell) {
    const field = cell.dataset.prizeField;
    const current = row.dataset[field];
    const input = document.createElement('input');
    input.className = 'form-control form-control-sm';
    input.type = field === 'quantity' ? 'number' : 'text';
    if (field === 'quantity') {
        input.min = 1;
    } else {
        input.maxLength = 255;
    }
    input.value = current;

    editing = true;
    cell.replaceChildren(input);
    input.focus();
    input.select();

    let finished = false;
    const finish = () => {
        finished = true;
        editing = false;
    };

    const cancel = () => {
        if (finished) return;
        finish();
        renderPrizeList(lastPrizes, true);
    };

    const save = async () => {
        if (finished) return;
        if (input.value.trim() === current) return cancel();
        finish();
        input.disabled = true;

        try {
            publish(await call('PATCH', itemUrl(row.dataset.prizeId), { field, value: input.value.trim() }));
            toast('success', 'Hadiah diperbarui');
        } catch (error) {
            renderPrizeList(lastPrizes, true);
            toast('error', error.message);
        }
    };

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); save(); }
        if (event.key === 'Escape') { event.preventDefault(); cancel(); }
    });
    input.addEventListener('blur', save);
}
