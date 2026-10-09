// Editor alokasi hadiah di panel kontrol undian: daftar baris "hadiah × jumlah". Slot ke-n mendapat hadiah ke-n,
// jadi urutan baris menentukan hadiah tiap posisi. Satu baris boleh dikosongkan jumlahnya = memakai sisa slot.

const STORAGE_KEY = 'doorprize.allocation';
const MAX_ROWS = 10;

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[m]));

function load() {
    try {
        const rows = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        if (Array.isArray(rows) && rows.length > 0) {
            return rows.slice(0, MAX_ROWS).map((r) => ({ prizeId: String(r.prizeId ?? ''), count: String(r.count ?? '') }));
        }
    } catch (e) { /* tanpa sessionStorage */ }

    return [{ prizeId: '', count: '' }];
}

export function createAllocation(container, onChange) {
    const rowsEl = container.querySelector('[data-allocation-rows]');
    const addBtn = container.querySelector('[data-allocation-add]');
    const summaryEl = container.querySelector('[data-allocation-summary]');
    const progressEl = container.querySelector('[data-allocation-progress]');

    let prizes = [];
    let rows = load();
    let signature = '';
    let dirtyWhileFocused = false;

    const persist = () => {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(rows)); } catch (e) { /* abaikan */ }
    };

    function render() {
        rowsEl.innerHTML = rows.map((row, i) => {
            const prize = prizes.find((p) => String(p.id) === row.prizeId);
            const options = prizes.map((p) => `<option value="${p.id}" ${String(p.id) === row.prizeId ? 'selected' : ''} ${p.remaining < 1 ? 'disabled' : ''}>${escapeHtml(p.name)} (sisa ${p.remaining} dari ${p.quantity})</option>`).join('');
            const thumb = prize?.image_url
                ? `<img src="${escapeHtml(prize.image_url)}" alt="${escapeHtml(prize.name)}">`
                : '<span aria-hidden="true">🎁</span>';

            return `
                <div class="allocation-row" data-allocation-row="${i}">
                    <span class="badge bg-primary-lt allocation-slot">Slot ${slotRange(i)}</span>
                    <span class="allocation-thumb">${thumb}</span>
                    <select class="form-select form-select-sm" data-allocation-prize aria-label="Hadiah baris ${i + 1}">
                        <option value="">— Pilih hadiah —</option>${options}
                    </select>
                    <div class="input-group input-group-sm allocation-count">
                        <button type="button" class="btn btn-outline-secondary px-2" data-allocation-step="-1" aria-label="Kurangi jumlah">−</button>
                        <input type="number" min="1" max="10" class="form-control px-1" placeholder="sisa" value="${escapeHtml(row.count)}" data-allocation-count aria-label="Jumlah baris ${i + 1}">
                        <button type="button" class="btn btn-outline-secondary px-2" data-allocation-step="1" aria-label="Tambah jumlah">+</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" data-allocation-remove title="Hapus baris" aria-label="Hapus baris ${i + 1}" ${rows.length === 1 ? 'disabled' : ''}>×</button>
                </div>`;
        }).join('');
        addBtn.disabled = rows.length >= MAX_ROWS;
        dirtyWhileFocused = false;
    }

    // Label rentang slot per baris ("1", "2-3", ...) dari jumlah eksplisit sebelum baris itu; baris tanpa jumlah tak diketahui di sini.
    function slotRange(index) {
        let start = 1;
        for (let i = 0; i < index; i++) {
            const n = parseInt(rows[i].count, 10);
            if (!n) return `ke-${index + 1}`;
            start += n;
        }
        const n = parseInt(rows[index].count, 10);
        if (!n) return `${start}+`;

        return n === 1 ? String(start) : `${start}-${start + n - 1}`;
    }

    function setPrizes(next) {
        prizes = next;
        const sig = JSON.stringify(next.map((p) => [p.id, p.name, p.remaining, p.quantity, p.image_url]));
        if (sig === signature) return;
        signature = sig;

        // Pilihan hadiah yang sudah dihapus dikosongkan.
        rows = rows.map((r) => (prizes.some((p) => String(p.id) === r.prizeId) ? r : { ...r, prizeId: '' }));
        persist();

        // Jangan membongkar baris saat operator sedang mengisi; ditunda sampai fokus lepas.
        if (container.contains(document.activeElement) && document.activeElement !== document.body) {
            dirtyWhileFocused = true;
        } else {
            render();
        }
    }

    // Ubah baris menjadi daftar [prize_id, count] untuk `slots` pemenang, atau pesan kenapa belum bisa.
    function resolve(slots) {
        if (prizes.length === 0) return { error: 'Tambah hadiah dulu di Daftar Hadiah.' };
        if (rows.some((r) => r.prizeId === '')) {
            return { error: rows.length === 1 ? 'Pilih hadiah yang diundi dulu.' : 'Pilih hadiah pada setiap baris alokasi.' };
        }

        const blanks = rows.filter((r) => !parseInt(r.count, 10));
        if (blanks.length > 1) return { error: 'Hanya satu baris yang boleh dikosongkan jumlahnya (memakai sisa slot).' };

        const explicit = rows.reduce((acc, r) => acc + (parseInt(r.count, 10) || 0), 0);
        const result = rows.map((r) => ({ prize_id: Number(r.prizeId), count: parseInt(r.count, 10) || 0 }));

        if (blanks.length === 1) {
            const rest = slots - explicit;
            if (rest < 1) return { error: `Alokasi ${explicit} sudah melebihi ${slots} pemenang; tidak ada sisa slot untuk baris tanpa jumlah.` };
            result[rows.indexOf(blanks[0])].count = rest;
        } else if (explicit !== slots) {
            return { error: `Alokasi hadiah ${explicit} harus sama dengan ${slots} pemenang.` };
        }

        const needed = new Map();
        result.forEach((r) => needed.set(r.prize_id, (needed.get(r.prize_id) ?? 0) + r.count));
        for (const [id, need] of needed) {
            const prize = prizes.find((p) => p.id === id);
            if (prize && prize.remaining < need) {
                return { error: `Sisa hadiah "${prize.name}" hanya ${prize.remaining}, kurang dari ${need} yang dialokasikan.` };
            }
        }

        return { rows: result };
    }

    // Jumlah slot yang sudah terisi alokasi; satu baris tanpa jumlah memakai sisa slot.
    function allocated(slots) {
        const explicit = rows.reduce((acc, r) => acc + (parseInt(r.count, 10) || 0), 0);
        const blanks = rows.filter((r) => !parseInt(r.count, 10)).length;

        return blanks === 1 && slots - explicit >= 1 ? slots : explicit;
    }

    function setProgress(slots) {
        if (!progressEl) return;
        const done = allocated(slots);
        progressEl.textContent = `${done} / ${slots} slot`;
        progressEl.className = `badge fs-6 px-2 py-1 text-nowrap ${slots > 0 && done === slots ? 'bg-success-lt' : 'bg-warning-lt'}`;
    }

    function setSummary(text, tone = 'secondary') {
        summaryEl.textContent = text;
        summaryEl.className = `small text-${tone}`;
    }

    rowsEl.addEventListener('change', (event) => {
        const i = Number(event.target.closest('[data-allocation-row]')?.dataset.allocationRow);
        if (event.target.matches('[data-allocation-prize]')) {
            rows[i].prizeId = event.target.value;
            persist();
            render();
            onChange();
        }
    });

    rowsEl.addEventListener('input', (event) => {
        const i = Number(event.target.closest('[data-allocation-row]')?.dataset.allocationRow);
        if (event.target.matches('[data-allocation-count]')) {
            rows[i].count = event.target.value;
            persist();
            onChange();
        }
    });

    // Nomor slot di label ikut berubah saat jumlah diubah; render ulang setelah selesai mengetik.
    rowsEl.addEventListener('focusout', () => {
        setTimeout(() => {
            if (!rowsEl.contains(document.activeElement)) render();
        }, 0);
    });

    rowsEl.addEventListener('click', (event) => {
        const step = event.target.closest('[data-allocation-step]');
        if (step) {
            const i = Number(step.closest('[data-allocation-row]').dataset.allocationRow);
            const next = (parseInt(rows[i].count, 10) || 0) + Number(step.dataset.allocationStep);
            // Di bawah 1 = kosong (memakai sisa slot)
            rows[i].count = next < 1 ? '' : String(Math.min(10, next));
            persist();
            render();
            onChange();
            return;
        }

        if (!event.target.closest('[data-allocation-remove]')) return;
        const i = Number(event.target.closest('[data-allocation-row]').dataset.allocationRow);
        rows.splice(i, 1);
        persist();
        render();
        onChange();
    });

    addBtn.addEventListener('click', () => {
        if (rows.length >= MAX_ROWS) return;
        rows.push({ prizeId: '', count: '1' });
        persist();
        render();
        onChange();
    });

    render();

    // Kembali ke satu baris kosong (dipanggil setelah undian selesai, agar putaran berikutnya diatur dari awal).
    function reset() {
        rows = [{ prizeId: '', count: '' }];
        persist();
        render();
        onChange();
    }

    return { setPrizes, resolve, setSummary, setProgress, reset };
}
