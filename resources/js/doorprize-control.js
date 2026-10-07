import Swal from 'sweetalert2';

// Kontrol undian di dasbor /doorprize: Start/Stop mengirim perintah ke server, layar TV membacanya lewat polling.
const root = document.getElementById('spin-control');

if (root) {
    const statusEl = root.querySelector('[data-spin-status]');
    const hintEl = root.querySelector('[data-spin-hint]');
    const winnersEl = root.querySelector('[data-spin-winners]');
    const startBtn = root.querySelector('[data-spin-action="start"]');
    const stopBtn = root.querySelector('[data-spin-action="stop"]');
    const durationInput = root.querySelector('[data-spin-duration]');
    const durationSave = root.querySelector('[data-spin-duration-save]');
    const resetBtn = root.querySelector('[data-spin-reset]');
    const resetHint = root.querySelector('[data-spin-reset-hint]');

    const allCategories = JSON.parse(root.dataset.allCategories || '[]');
    let selectedSlots = Number(root.dataset.slots) || 5;
    let selectedCategories = JSON.parse(root.dataset.categories || '[]');
    let eligibleByCategory = JSON.parse(root.dataset.eligibleByCategory || '{}');

    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    const labels = {
        idle: 'Siap memulai undian',
        spinning: 'Slot sedang berputar...',
        stopped: 'Undian selesai',
    };

    let current = {
        status: root.dataset.status,
        eligible: Number(root.dataset.eligible) || 0,
        eligible_total: Number(root.dataset.eligibleTotal) || 0,
        eligible_by_category: eligibleByCategory,
        won: Number(root.dataset.won),
        winners: [],
        slots: selectedSlots,
        categories: selectedCategories,
        remaining_ms: null,
    };
    let deadline = null;
    let expiryHandled = false;

    function calculateEligible(cats) {
        if (!cats || cats.length === 0 || cats.length >= allCategories.length) {
            return current.eligible_total || 0;
        }
        return cats.reduce((acc, c) => acc + (eligibleByCategory[c] || 0), 0);
    }

    function markWinners(names) {
        document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
            if (!names.includes(row.dataset.name) || row.dataset.status === 'PEMENANG') {
                return;
            }

            row.dataset.status = 'PEMENANG';
            const badge = document.createElement('span');
            badge.className = 'badge bg-green-lt';
            badge.textContent = 'PEMENANG';
            // Status cell is column 4 (index 3) now that category is column 3 (index 2)
            const statusCell = row.querySelector('[data-col-status]') || row.children[3] || row.children[2];
            statusCell.replaceChildren(badge);
        });

        window.Livewire?.dispatch('participants-changed');
    }

    function unmarkWinners() {
        document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
            if (row.dataset.status === 'PEMENANG') {
                row.dataset.status = '';
                const statusCell = row.querySelector('[data-col-status]') || row.children[3] || row.children[2];
                statusCell.replaceChildren();
            }
        });

        window.Livewire?.dispatch('participants-changed');
    }

    function hintText() {
        const { status } = current;
        const eligible = calculateEligible(selectedCategories);
        const catLabel = selectedCategories.length > 0 && selectedCategories.length < allCategories.length
            ? `kategori terpilih (${selectedCategories.join(', ')})`
            : 'semua kategori';

        const remaining = `${eligible} peserta belum menang (${catLabel}).`;

        if (status === 'spinning') {
            if (deadline !== null) {
                const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                return `Berhenti otomatis dalam ${left} detik, atau klik Stop sekarang.`;
            }
            return `Slot sedang berputar (${selectedSlots} pemenang). Klik Stop untuk menghentikan dan menampilkan pemenang.`;
        }

        if (eligible < selectedSlots) {
            return `Minimal ${selectedSlots} peserta yang belum menang diperlukan untuk undian dari ${catLabel} (tersisa ${eligible}). Tambah peserta atau ubah pilihan.`;
        }

        return status === 'stopped'
            ? `Klik Start untuk undian berikutnya (${selectedSlots} pemenang). ${remaining}`
            : `Pastikan layar TV sudah terbuka, lalu klik Start (${selectedSlots} pemenang). ${remaining}`;
    }

    function updateControlsUI() {
        // Update slot buttons active styling
        root.querySelectorAll('[data-spin-slot]').forEach((btn) => {
            const s = Number(btn.dataset.spinSlot);
            const isActive = s === selectedSlots;
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline-secondary', !isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        // Update category checkboxes
        root.querySelectorAll('[data-spin-category]').forEach((cb) => {
            const cat = cb.value;
            const isChecked = selectedCategories.length === 0 || selectedCategories.length >= allCategories.length || selectedCategories.includes(cat);
            cb.checked = isChecked;

            const badge = cb.closest('label')?.querySelector('[data-cat-count]');
            if (badge) {
                badge.textContent = eligibleByCategory[cat] ?? 0;
            }
        });

        const activeEligible = calculateEligible(selectedCategories);
        startBtn.disabled = activeEligible < selectedSlots || current.status === 'spinning';
        hintEl.textContent = hintText();
    }

    function render(state) {
        if (state.status === 'stopped' && current.status !== 'stopped') {
            markWinners(state.winners);
        }
        if (state.won === 0 && current.won > 0) {
            unmarkWinners();
        }

        current = state;
        if (state.slots) {
            selectedSlots = state.slots;
        }
        if (Array.isArray(state.categories)) {
            selectedCategories = state.categories;
        }
        if (state.eligible_by_category) {
            eligibleByCategory = state.eligible_by_category;
        }

        deadline = state.status === 'spinning' && state.remaining_ms !== null ? Date.now() + state.remaining_ms : null;
        expiryHandled = false;

        root.dataset.status = state.status;
        statusEl.textContent = labels[state.status] ?? labels.idle;
        statusEl.classList.toggle('text-success', state.status === 'spinning');

        stopBtn.disabled = state.status !== 'spinning';
        resetBtn.disabled = state.won < 1 || state.status === 'spinning';
        resetHint.textContent = state.won > 0 ? `${state.won} peserta berstatus pemenang.` : 'Belum ada pemenang.';

        const winners = state.status === 'stopped' ? state.winners : [];
        winnersEl.replaceChildren(...winners.map((name, idx) => {
            const li = document.createElement('li');
            li.textContent = `#${idx + 1}: ${name}`;
            return li;
        }));
        winnersEl.hidden = winners.length === 0;

        updateControlsUI();
    }

    async function post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message ?? `HTTP ${response.status}`));
        }

        return data;
    }

    async function send(action) {
        const button = action === 'start' ? startBtn : stopBtn;
        button.disabled = true;

        try {
            const body = action === 'start'
                ? { slots: selectedSlots, categories: selectedCategories }
                : undefined;
            render(await post(root.dataset[`${action}Url`], body));
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
            await refresh();
        }
    }

    async function refresh() {
        try {
            const response = await fetch(root.dataset.stateUrl, { headers: { 'Accept': 'application/json' } });
            render(await response.json());
        } catch (error) {}
    }

    // Event listener: Klik tombol start/stop
    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-spin-action]');
        if (button && !button.disabled) {
            send(button.dataset.spinAction);
            return;
        }

        // Klik pilihan jumlah slot pemenang (1..10)
        const slotBtn = event.target.closest('[data-spin-slot]');
        if (slotBtn && current.status !== 'spinning') {
            selectedSlots = Number(slotBtn.dataset.spinSlot);
            updateControlsUI();
            if (root.dataset.configureUrl) {
                post(root.dataset.configureUrl, { slots: selectedSlots, categories: selectedCategories }).catch(() => {});
            }
            return;
        }

        // Klik tombol pilih semua kategori
        const allCatBtn = event.target.closest('[data-spin-all-categories]');
        if (allCatBtn && current.status !== 'spinning') {
            selectedCategories = [];
            root.querySelectorAll('[data-spin-category]').forEach(cb => cb.checked = true);
            updateControlsUI();
            if (root.dataset.configureUrl) {
                post(root.dataset.configureUrl, { slots: selectedSlots, categories: selectedCategories }).catch(() => {});
            }
            return;
        }
    });

    // Checkbox toggle kategori
    root.addEventListener('change', (event) => {
        const catCb = event.target.closest('[data-spin-category]');
        if (catCb && current.status !== 'spinning') {
            const checked = Array.from(root.querySelectorAll('[data-spin-category]:checked')).map(cb => cb.value);
            // Bila semua dicentang, kosongkan array (berarti semua kategori)
            selectedCategories = checked.length >= allCategories.length ? [] : checked;
            updateControlsUI();
            if (root.dataset.configureUrl) {
                post(root.dataset.configureUrl, { slots: selectedSlots, categories: selectedCategories }).catch(() => {});
            }
        }
    });

    durationSave.addEventListener('click', async () => {
        durationSave.disabled = true;

        try {
            const state = await post(root.dataset.durationUrl, { duration: durationInput.value === '' ? null : Number(durationInput.value) });
            durationInput.value = state.duration;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Durasi disimpan',
                timer: 1500,
                showConfirmButton: false,
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
        } finally {
            durationSave.disabled = false;
        }
    });

    resetBtn.addEventListener('click', async () => {
        const { isConfirmed } = await Swal.fire({
            icon: 'warning',
            title: 'Reset semua pemenang?',
            text: `Status PEMENANG dari ${current.won} peserta akan dihapus sehingga mereka bisa diundi lagi, dan layar TV dikosongkan.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, reset',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d63939',
            focusCancel: true,
        });

        if (!isConfirmed) {
            return;
        }

        resetBtn.disabled = true;

        try {
            const state = await post(root.dataset.resetUrl);
            render(state);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `${state.reset} pemenang direset`,
                timer: 2000,
                showConfirmButton: false,
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
            await refresh();
        }
    });

    setInterval(() => {
        if (current.status !== 'spinning' || deadline === null) {
            return;
        }

        hintEl.textContent = hintText();

        if (Date.now() >= deadline && !expiryHandled) {
            expiryHandled = true;
            refresh();
        }
    }, 250);

    refresh();
    setInterval(refresh, 3000);
}
