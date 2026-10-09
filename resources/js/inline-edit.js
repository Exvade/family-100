import Swal from 'sweetalert2';
import { renderWonAt } from './won-at';

// Edit langsung di sel tabel peserta: klik sel `[data-inline]` (name | category | status) pada baris
// `tr[data-row][data-update-url]`. Enter/blur menyimpan, Esc membatalkan; kolom Nomor tidak bisa diedit.

// Nilai tersimpan tetap baku; label tampilan datang dari server lewat data-category-labels pada tabel.
const labelsOf = (el) => JSON.parse(el.closest('[data-datatable]')?.dataset.categoryLabels || '{}');
const BADGE = {
    'Keluarga CPP': 'indigo',
    'Keluarga CPW': 'pink',
    'Teman CPP': 'cyan',
    'Teman CPW': 'purple',
};

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function show(cell, field, data) {
    if (field === 'name') {
        cell.textContent = data.name;
    } else if (field === 'category') {
        const badge = document.createElement('span');
        badge.className = `badge bg-${BADGE[data.category] ?? 'azure'}-lt`;
        badge.textContent = labelsOf(cell)[data.category] ?? data.category;
        cell.replaceChildren(badge);
    } else if (data.status === 'PEMENANG') {
        const badge = document.createElement('span');
        badge.className = 'badge bg-green-lt';
        badge.textContent = 'PEMENANG';
        if (data.won_at_title) {
            badge.title = `Terpilih ${data.won_at_title}`;
        }
        cell.replaceChildren(badge);
    } else {
        cell.replaceChildren();
    }
}

function syncRow(row, data) {
    row.dataset.name = data.name;
    row.dataset.category = data.category;
    row.dataset.categoryLabel = labelsOf(row)[data.category] ?? data.category;
    row.dataset.status = data.status;
    renderWonAt(row, data);
    syncTabCounts();
    window.dispatchEvent(new CustomEvent('datatable:refresh'));
}

// Angka di tab kategori dihitung ulang dari baris yang ada di tabel.
function syncTabCounts() {
    const rows = Array.from(document.querySelectorAll('[data-datatable] tr[data-row]'));
    document.querySelectorAll('[data-dt-category]').forEach((tab) => {
        const cat = tab.dataset.dtCategory;
        const badge = tab.querySelector('.badge');
        if (!badge || cat === 'PEMENANG') return;
        badge.textContent = cat ? rows.filter((r) => r.dataset.category === cat).length : rows.length;
    });
}

function toast(icon, title) {
    Swal.fire({ toast: true, position: 'top-end', icon, title, timer: 2200, showConfirmButton: false });
}

function edit(cell) {
    const row = cell.closest('tr[data-row][data-update-url]');
    const field = cell.dataset.inline;
    if (!row || cell.querySelector('[data-inline-input]')) return;

    const original = Array.from(cell.childNodes).map((n) => n.cloneNode(true));
    const current = row.dataset[field];
    let control;

    if (field === 'name') {
        control = document.createElement('input');
        control.type = 'text';
        control.maxLength = 255;
        control.className = 'form-control form-control-sm';
    } else {
        control = document.createElement('select');
        control.className = 'form-select form-select-sm';
        const options = field === 'category'
            ? Object.entries(labelsOf(cell))
            : [['', '— (belum menang)'], ['PEMENANG', 'PEMENANG']];
        options.forEach(([value, label]) => control.add(new Option(label, value)));
    }
    control.value = current;
    control.dataset.inlineInput = '';
    cell.replaceChildren(control);
    control.focus();
    if (field === 'name') control.select();

    let finished = false;
    const cancel = () => {
        if (finished) return;
        finished = true;
        cell.replaceChildren(...original);
    };

    const save = async () => {
        if (finished) return;
        const value = control.value;
        if (value === current || (field === 'name' && value.trim() === current)) {
            return cancel();
        }
        finished = true;
        control.disabled = true;

        try {
            const response = await fetch(row.dataset.updateUrl, {
                method: 'PATCH',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ field, value }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message ?? `HTTP ${response.status}`));
            }
            // Perbarui tampilan seluruh sel baris yang terpengaruh (nama/kategori/status sekaligus).
            row.querySelectorAll('[data-inline]').forEach((c) => {
                if (c !== cell) show(c, c.dataset.inline, data);
            });
            show(cell, field, data);
            syncRow(row, data);
            toast('success', 'Peserta diperbarui');
        } catch (err) {
            cell.replaceChildren(...original);
            toast('error', err.message);
        }
    };

    control.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); save(); }
        if (e.key === 'Escape') { e.preventDefault(); cancel(); }
    });
    control.addEventListener('blur', save);
    if (field !== 'name') control.addEventListener('change', save);
}

document.addEventListener('click', (event) => {
    const cell = event.target.closest('[data-datatable] td[data-inline]');
    if (cell) edit(cell);
});
