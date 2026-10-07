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
    const slots = Number(root.dataset.slots);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    const labels = {
        idle: 'Siap memulai undian',
        spinning: 'Slot sedang berputar...',
        stopped: 'Undian selesai',
    };

    let current = { status: root.dataset.status, eligible: 0, won: Number(root.dataset.won), winners: [], remaining_ms: null };
    let deadline = null;       // waktu (Date.now) undian berhenti otomatis, bila durasi diatur
    let expiryHandled = false;

    // Setelah Stop, pemenang baru langsung diberi status di daftar peserta (tabel desktop dan daftar mobile).
    function markWinners(names) {
        document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
            if (!names.includes(row.dataset.name) || row.dataset.status === 'PEMENANG') {
                return;
            }

            row.dataset.status = 'PEMENANG';
            const badge = document.createElement('span');
            badge.className = 'badge bg-green-lt';
            badge.textContent = 'PEMENANG';
            row.children[2].replaceChildren(badge);
        });

        window.Livewire?.dispatch('participants-changed');
    }

    // Setelah reset, badge PEMENANG di daftar peserta dihapus.
    function unmarkWinners() {
        document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
            if (row.dataset.status === 'PEMENANG') {
                row.dataset.status = '';
                row.children[2].replaceChildren();
            }
        });

        window.Livewire?.dispatch('participants-changed');
    }

    function hintText() {
        const { status, eligible } = current;
        const remaining = `${eligible} peserta belum menang.`;

        if (status === 'spinning') {
            if (deadline !== null) {
                const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                return `Berhenti otomatis dalam ${left} detik, atau klik Stop sekarang.`;
            }
            return 'Klik Stop untuk menghentikan semua slot dan menampilkan pemenang.';
        }
        if (eligible < slots) {
            return `Minimal ${slots} peserta yang belum menang diperlukan untuk undian (tersisa ${eligible}). Tambah peserta untuk melanjutkan.`;
        }

        return status === 'stopped'
            ? `Klik Start untuk undian berikutnya. ${remaining}`
            : `Pastikan halaman TV sudah terbuka, lalu klik Start. ${remaining}`;
    }

    function render(state) {
        if (state.status === 'stopped' && current.status !== 'stopped') {
            markWinners(state.winners);
        }
        if (state.won === 0 && current.won > 0) {
            unmarkWinners();
        }
        current = state;

        deadline = state.status === 'spinning' && state.remaining_ms !== null ? Date.now() + state.remaining_ms : null;
        expiryHandled = false;

        root.dataset.status = state.status;
        statusEl.textContent = labels[state.status] ?? labels.idle;
        statusEl.classList.toggle('text-success', state.status === 'spinning');

        startBtn.disabled = state.eligible < slots || state.status === 'spinning';
        stopBtn.disabled = state.status !== 'spinning';
        hintEl.textContent = hintText();

        resetBtn.disabled = state.won < 1 || state.status === 'spinning';
        resetHint.textContent = state.won > 0 ? `${state.won} peserta berstatus pemenang.` : 'Belum ada pemenang.';

        const winners = state.status === 'stopped' ? state.winners : [];
        winnersEl.replaceChildren(...winners.map((name) => {
            const li = document.createElement('li');
            li.textContent = name;
            return li;
        }));
        winnersEl.hidden = winners.length === 0;
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
            render(await post(root.dataset[`${action}Url`]));
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
            await refresh();
        }
    }

    // Status bisa berubah dari tab/perangkat lain atau karena waktu spin habis; sinkronkan agar tombol tidak salah aktif.
    async function refresh() {
        try {
            const response = await fetch(root.dataset.stateUrl, { headers: { 'Accept': 'application/json' } });
            render(await response.json());
        } catch (error) { /* abaikan, coba lagi di polling berikutnya */ }
    }

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-spin-action]');
        if (button && !button.disabled) {
            send(button.dataset.spinAction);
        }
    });

    durationSave.addEventListener('click', async () => {
        durationSave.disabled = true;

        try {
            const state = await post(root.dataset.durationUrl, { duration: durationInput.value === '' ? null : Number(durationInput.value) });
            durationInput.value = state.duration;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Durasi disimpan', timer: 1500, showConfirmButton: false });
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
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${state.reset} pemenang direset`, timer: 2000, showConfirmButton: false });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
            await refresh();
        }
    });

    // Hitung mundur lokal; begitu habis, minta status terbaru (server yang menghentikan undian dan memilih pemenang).
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
