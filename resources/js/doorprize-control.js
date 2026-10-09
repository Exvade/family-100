import Swal from 'sweetalert2';
import { renderWonAt } from './won-at';
import { renderPrizeList } from './prizes';
import { createAllocation } from './allocation';

// Kontrol undian di dasbor /doorprize: Start/Stop mengirim perintah ke server, layar TV membacanya lewat polling.
const root = document.getElementById('spin-control');

if (root) {
    const statusEl = root.querySelector('[data-spin-status]');
    const hintEl = root.querySelector('[data-spin-hint]');
    const stopBtn = root.querySelector('[data-spin-action="stop"]');
    const durationInput = root.querySelector('[data-spin-duration]');
    const durationSave = root.querySelector('[data-spin-duration-save]');
    const resetBtn = root.querySelector('[data-spin-reset]');
    const resetHint = root.querySelector('[data-spin-reset-hint]');

    let eligibleByCategory = JSON.parse(root.dataset.eligibleByCategory || '{}');
    // Kuota tersimpan per kategori; jumlah pemenang pada tombol trigger cepat mengikuti ini.
    let quotaSetting = JSON.parse(root.dataset.quotaSetting || '{}');
    // True saat input kuota diubah tapi belum disimpan; polling tidak boleh menimpa total dengan nilai server.
    let quotaDirty = false;
    // Hadiah yang bisa diundi dan editor alokasinya (hadiah per slot, urut slot); pilihan operator diingat selama sesi tab ini.
    let prizes = JSON.parse(root.dataset.prizes || '[]');
    const allocation = createAllocation(root.querySelector('[data-allocation]'), () => updateControlsUI());

    // Diisi oleh form kuota di bawah: simpan perubahan yang tertunda, true bila aman dilanjutkan.
    let flushQuotaSave = async () => true;

    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[m]));
    }

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
        remaining_ms: null,
    };
    let deadline = null;
    let expiryHandled = false;

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
        window.dispatchEvent(new CustomEvent('datatable:refresh'));
    }

    function unmarkWinners() {
        document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
            if (row.dataset.status === 'PEMENANG') {
                row.dataset.status = '';
                const statusCell = row.querySelector('[data-col-status]') || row.children[3] || row.children[2];
                statusCell.replaceChildren();
                renderWonAt(row, null);
            }
        });

        window.Livewire?.dispatch('participants-changed');
        window.dispatchEvent(new CustomEvent('datatable:refresh'));
    }

    // Jumlah pemenang yang akan terundi oleh tombol utama (total kuota, dibatasi peserta di kategori berkuota)
    function drawableSlots() {
        const quotaSum = Object.values(quotaSetting).reduce((acc, n) => acc + n, 0);
        const eligibleInQuotaCats = Object.entries(quotaSetting)
            .reduce((acc, [cat, n]) => acc + (n > 0 ? (eligibleByCategory[cat] ?? 0) : 0), 0);

        return Math.min(quotaSum, eligibleInQuotaCats);
    }

    // Alasan undian dengan `slots` pemenang belum boleh dimulai karena alokasi hadiahnya; string kosong bila boleh.
    function prizeReason(slots) {
        return allocation.resolve(slots).error ?? '';
    }

    function hintText() {
        const { status } = current;

        if (status === 'spinning') {
            if (deadline !== null) {
                const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                return `Berhenti otomatis dalam ${left} detik, atau klik Stop sekarang.`;
            }
            return `Slot sedang berputar (${current.slots} pemenang). Klik Stop untuk menghentikan dan menampilkan pemenang.`;
        }

        const remaining = `${current.eligible_total || 0} peserta belum menang.`;

        return status === 'stopped'
            ? `Pilih trigger di bawah untuk undian berikutnya. ${remaining}`
            : `Pastikan layar TV sudah terbuka, lalu pilih salah satu trigger di bawah. ${remaining}`;
    }

    function updateControlsUI() {
        // Kategori yang kuotanya melebihi sisa peserta; dipakai untuk mematikan trigger utama.
        const shortages = [];
        const quotaSum = Object.values(quotaSetting).reduce((acc, n) => acc + n, 0);

        // Update Trigger Cepat per Kategori buttons
        root.querySelectorAll('[data-trigger-category]').forEach((btn) => {
            const cat = btn.dataset.triggerCategory;
            const quota = quotaSetting[cat] ?? 0;
            const eligible = eligibleByCategory[cat] ?? 0;
            const spinning = current.status === 'spinning';
            if (eligible < quota) {
                const label = btn.querySelector('.fw-bold')?.textContent.trim() || cat;
                shortages.push(`${label} (butuh ${quota}, tersisa ${eligible})`);
            }
            // Alasan tombol mati ditulis di tombolnya; urutan harus sama dengan $catNote di doorprize.blade.php
            let note = `(${quota} pemenang)`;
            if (quota < 1) {
                note = 'Kuota 0, atur dulu di Pengaturan Kuota';
            } else if (eligible < 1) {
                note = 'Semua peserta sudah menang / belum ada peserta';
            } else if (eligible < quota) {
                note = `Peserta kurang: butuh ${quota}, tersisa ${eligible}`;
            } else if (quotaSum > 10) {
                note = 'Total kuota melebihi 10, perbaiki dulu di Pengaturan Kuota';
            } else if (prizeReason(quota)) {
                note = prizeReason(quota);
            } else if (spinning) {
                note = 'Undian sedang berputar';
            }
            btn.disabled = spinning || quota < 1 || eligible < quota || quotaSum > 10 || prizeReason(quota) !== '';
            btn.title = btn.disabled ? note : `Putar undian ${quota} pemenang dari ${cat}`;
            const badge = btn.querySelector(`[data-trigger-cat-count="${cat}"]`);
            if (badge) {
                badge.textContent = note;
            }
        });

        // Update Trigger Sesuai Kuota button: undian berjalan seadanya (tiap kategori sebanyak yang tersedia, maks. sebesar kuotanya)
        const triggerQuotaBtn = root.querySelector('[data-trigger-action="start-quota"]');
        if (triggerQuotaBtn) {
            // Slot = total kuota, dibatasi peserta belum menang di kategori berkuota; kekurangan diisi dari kategori berkuota lain.
            const drawable = drawableSlots();
            let reason = '';
            let info = '';
            if (quotaSum < 1) {
                reason = 'Kuota belum diatur. Isi kuota minimal 1 pemenang di Pengaturan Kuota.';
            } else if (quotaSum > 10) {
                reason = 'Total kuota maksimal 10 pemenang.';
            } else if (drawable < 1) {
                reason = 'Tidak ada peserta yang belum menang pada kategori yang berkuota.';
            } else if (prizeReason(drawable)) {
                reason = prizeReason(drawable);
            } else if (current.status === 'spinning') {
                reason = 'Undian sedang berputar.';
            } else if (shortages.length > 0) {
                info = drawable === quotaSum
                    ? `Peserta kurang di: ${shortages.join(', ')}. Kekurangannya diisi acak dari peserta kategori berkuota lain, total ${quotaSum} pemenang.`
                    : `Peserta kurang di: ${shortages.join(', ')}. Peserta tidak cukup untuk ${quotaSum} slot, undian berjalan dengan ${drawable} pemenang.`;
            }
            triggerQuotaBtn.disabled = reason !== '';
            const noteEl = root.querySelector('[data-trigger-quota-note]');
            if (noteEl) {
                noteEl.textContent = reason || info;
                noteEl.className = `small mt-2 ${reason ? 'text-danger' : 'text-warning-emphasis'}`;
                noteEl.hidden = reason === '' && info === '';
            }
        }

        // Ringkasan alokasi hadiah terhadap jumlah pemenang tombol utama
        const mainSlots = drawableSlots();
        allocation.setProgress(mainSlots);
        if (mainSlots > 0) {
            const resolved = allocation.resolve(mainSlots);
            allocation.setSummary(resolved.error ?? `Alokasi sesuai: ${mainSlots} pemenang.`, resolved.error ? 'danger' : 'success');
        } else {
            allocation.setSummary('Atur kuota untuk melihat jumlah pemenang.');
        }

        // Update Setting form eligible badges
        document.querySelectorAll('[data-quota-eligible]').forEach((badge) => {
            const cat = badge.dataset.quotaEligible;
            badge.textContent = eligibleByCategory[cat] ?? 0;
        });

        // Sisa peserta yang belum masuk kuota per kategori (tersedia dikurangi kuota), dan totalnya
        let leftTotal = 0;
        document.querySelectorAll('[data-quota-left]').forEach((el) => {
            const cat = el.dataset.quotaLeft;
            const diff = (eligibleByCategory[cat] ?? 0) - (quotaSetting[cat] ?? 0);
            if (diff < 0) {
                el.textContent = `· Kurang ${-diff} peserta`;
                el.className = 'text-danger fw-semibold';
            } else {
                leftTotal += diff;
                el.textContent = `· Belum masuk kuota: ${diff}`;
                el.className = 'text-secondary';
            }
        });
        const leftTotalEl = document.getElementById('quota-left-total');
        if (leftTotalEl) {
            leftTotalEl.innerHTML = `Peserta belum masuk kuota: <strong>${leftTotal}</strong>`;
        }

        hintEl.textContent = hintText();
    }

    function render(state) {
        if (state.status === 'stopped' && current.status !== 'stopped') {
            markWinners(state.winners);
            // Alokasi hadiah dan kuota sudah terpakai; putaran berikutnya diatur dari awal.
            allocation.reset();
        }
        if (state.won === 0 && current.won > 0) {
            unmarkWinners();
        }

        current = state;
        if (state.eligible_by_category) {
            eligibleByCategory = state.eligible_by_category;
        }
        // Selama form kuota berisi perubahan yang belum tersimpan, nilai di form yang berlaku, bukan nilai server.
        if (state.quota_setting && !quotaDirty) {
            quotaSetting = state.quota_setting;
            document.querySelectorAll('[data-quota-input]').forEach((input) => {
                if (input !== document.activeElement && quotaSetting[input.dataset.cat] !== undefined) {
                    input.value = quotaSetting[input.dataset.cat];
                }
            });
        }
        // Baris kuota hanya tampil untuk kategori yang masih punya peserta belum menang.
        if (state.eligible_by_category) {
            document.querySelectorAll('[data-quota-row]').forEach((row) => {
                const has = (state.eligible_by_category[row.dataset.quotaRow] ?? 0) > 0;
                row.classList.toggle('d-flex', has);
                row.classList.toggle('d-none', !has);
            });
            // Trigger cepat juga disembunyikan untuk kategori tanpa peserta belum menang.
            document.querySelectorAll('[data-trigger-col]').forEach((col) => {
                col.classList.toggle('d-none', (state.eligible_by_category[col.dataset.triggerCol] ?? 0) < 1);
            });
        }

        if (state.quota_total !== undefined && !quotaDirty) {
            const quotaBadge = document.getElementById('quota-total-badge');
            const quotaDisplay = document.getElementById('quota-total-display');
            if (quotaBadge) quotaBadge.textContent = `${state.quota_total} Pemenang`;
            if (quotaDisplay) quotaDisplay.textContent = state.quota_total;
            document.querySelectorAll('[data-trigger-quota-total]').forEach((el) => {
                el.textContent = state.quota_total;
            });
        }

        deadline = state.status === 'spinning' && state.remaining_ms !== null ? Date.now() + state.remaining_ms : null;
        expiryHandled = false;

        root.dataset.status = state.status;
        statusEl.textContent = labels[state.status] ?? labels.idle;
        statusEl.classList.toggle('text-success', state.status === 'spinning');

        stopBtn.disabled = state.status !== 'spinning';
        resetBtn.disabled = state.won < 1 || state.status === 'spinning';
        resetHint.textContent = state.won > 0 ? `${state.won} peserta berstatus pemenang.` : 'Belum ada pemenang.';

        // Badge tab Riwayat Pemenang
        const winnersCount = state.won ?? (Array.isArray(state.winners_history) ? state.winners_history.length : 0);
        const winnersTabBadge = document.getElementById('tab-winners-count');
        if (winnersTabBadge) {
            winnersTabBadge.textContent = winnersCount;
        }

        // Tombol unduh hanya muncul bila sudah ada pemenang
        const exportWinnersBtn = document.getElementById('btn-export-winners');
        if (exportWinnersBtn) {
            exportWinnersBtn.hidden = winnersCount < 1;
        }

        // Daftar hadiah dan pilihan hadiah di panel kontrol
        if (Array.isArray(state.prizes)) {
            prizes = state.prizes;
            allocation.setPrizes(prizes);
            renderPrizeList(prizes);
        }

        if (Array.isArray(state.winners_history)) {
            const winnersByName = new Map(state.winners_history.map((w) => [w.name, w]));
            let anyChange = false;
            document.querySelectorAll('[data-datatable] tr[data-row]').forEach((row) => {
                const winner = winnersByName.get(row.dataset.name);
                const isWinner = winner !== undefined;
                // Waktu menang diperbarui tiap polling agar teks relatif ("x menit lalu") tetap segar.
                if (isWinner) {
                    if (row.dataset.wonAt !== winner.won_at) anyChange = true;
                    renderWonAt(row, winner);
                } else if (row.dataset.wonAt) {
                    renderWonAt(row, null);
                    anyChange = true;
                }
                if (isWinner && row.dataset.status !== 'PEMENANG') {
                    row.dataset.status = 'PEMENANG';
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-green-lt';
                    badge.textContent = 'PEMENANG';
                    const statusCell = row.querySelector('[data-col-status]') || row.children[3] || row.children[2];
                    statusCell.replaceChildren(badge);
                    anyChange = true;
                } else if (!isWinner && row.dataset.status === 'PEMENANG') {
                    row.dataset.status = '';
                    const statusCell = row.querySelector('[data-col-status]') || row.children[3] || row.children[2];
                    statusCell.replaceChildren();
                    anyChange = true;
                }
            });
            if (anyChange) {
                window.dispatchEvent(new CustomEvent('datatable:refresh'));
            }
        }

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

    async function send(action, body) {
        const button = action === 'stop' ? stopBtn : null;
        if (button) button.disabled = true;

        try {
            render(await post(root.dataset[`${action}Url`], body));
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: error.message });
            await refresh();
        } finally {
            if (button) button.disabled = false;
        }
    }

    async function refresh() {
        try {
            const response = await fetch(root.dataset.stateUrl, { headers: { 'Accept': 'application/json' } });
            render(await response.json());
        } catch (error) {}
    }

    // Event listener: Klik tombol start/stop/trigger
    root.addEventListener('click', (event) => {
        // Trigger sesuai kuota
        const triggerQuota = event.target.closest('[data-trigger-action="start-quota"]');
        if (triggerQuota && !triggerQuota.disabled) {
            flushQuotaSave().then((ok) => {
                if (ok) send('start', { mode: 'quota', allocation: allocation.resolve(drawableSlots()).rows });
            });
            return;
        }

        // Trigger cepat per kategori
        const triggerCat = event.target.closest('[data-trigger-category]');
        if (triggerCat && !triggerCat.disabled) {
            const cat = triggerCat.dataset.triggerCategory;
            // Kuota dikirim eksplisit supaya hanya kuota kategori ini yang kembali ke 0 setelah diundi.
            flushQuotaSave().then((ok) => {
                if (ok) send('start', { mode: 'quota', quotas: { [cat]: quotaSetting[cat] ?? 0 }, allocation: allocation.resolve(quotaSetting[cat] ?? 0).rows });
            });
            return;
        }

        const button = event.target.closest('[data-spin-action]');
        if (button && !button.disabled) {
            send(button.dataset.spinAction);
            return;
        }
    });

    // Form pengaturan kuota: perubahan langsung memperbarui tombol dan tersimpan otomatis (debounce).
    const settingForm = document.getElementById('setting-lucky-draw-form');
    if (settingForm) {
        const MAX_TOTAL = 10;
        const statusEl = document.getElementById('quota-save-status');
        let saveTimer = null;
        let version = 0;

        const setStatus = (text, tone = 'secondary') => {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.className = `small text-center text-${tone}`;
        };

        function readQuotas() {
            const quotas = {};
            settingForm.querySelectorAll('[data-quota-input]').forEach((input) => {
                // Baris yang disembunyikan (kategori tanpa peserta belum menang) selalu berkuota 0.
                const hidden = input.closest('[data-quota-row]')?.classList.contains('d-none');
                quotas[input.dataset.cat] = hidden ? 0 : Math.max(0, parseInt(input.value, 10) || 0);
            });
            return quotas;
        }

        const sum = (quotas) => Object.values(quotas).reduce((acc, n) => acc + n, 0);
        const validTotal = (total) => total >= 1 && total <= MAX_TOTAL;

        function calcQuotaTotal() {
            const quotas = readQuotas();
            const total = sum(quotas);
            const badge = document.getElementById('quota-total-badge');
            const display = document.getElementById('quota-total-display');
            if (badge) badge.textContent = `${total} Pemenang`;
            if (display) display.textContent = total;
            document.querySelectorAll('[data-trigger-quota-total]').forEach((el) => {
                el.textContent = total;
            });
            quotaSetting = quotas;
            updateControlsUI();
            return { quotas, total };
        }

        async function saveQuotas() {
            clearTimeout(saveTimer);
            saveTimer = null;
            const { quotas, total } = calcQuotaTotal();
            if (!validTotal(total)) {
                setStatus(`Belum tersimpan: total kuota harus 1–${MAX_TOTAL} pemenang.`, 'danger');
                return false;
            }

            const myVersion = version;
            setStatus('Menyimpan…');
            try {
                const res = await post(settingForm.dataset.saveUrl, { quotas });
                if (myVersion === version) {
                    quotaDirty = false;
                    render(res);
                    setStatus('Tersimpan otomatis.', 'success');
                }
                return true;
            } catch (err) {
                setStatus(`Gagal menyimpan: ${err.message}`, 'danger');
                return false;
            }
        }

        function onQuotaChange() {
            version += 1;
            quotaDirty = true;
            const { total } = calcQuotaTotal();
            clearTimeout(saveTimer);
            if (!validTotal(total)) {
                setStatus(`Belum tersimpan: total kuota harus 1–${MAX_TOTAL} pemenang.`, 'danger');
                return;
            }
            setStatus('Menyimpan…');
            saveTimer = setTimeout(saveQuotas, 400);
        }

        flushQuotaSave = async () => {
            if (!quotaDirty) return true;
            const ok = await saveQuotas();
            if (!ok) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: `Total kuota harus 1–${MAX_TOTAL} pemenang.`, timer: 2500, showConfirmButton: false });
            }
            return ok;
        };

        // Step buttons (+/-)
        settingForm.addEventListener('click', (event) => {
            const stepBtn = event.target.closest('[data-quota-step]');
            if (!stepBtn) return;
            const cat = stepBtn.dataset.cat;
            const step = parseInt(stepBtn.dataset.quotaStep, 10);
            const input = settingForm.querySelector(`[data-quota-input][data-cat="${cat}"]`);
            if (input) {
                const currentVal = Math.max(0, parseInt(input.value, 10) || 0);
                input.value = Math.max(0, Math.min(MAX_TOTAL, currentVal + step));
                onQuotaChange();
            }
        });

        settingForm.addEventListener('input', (event) => {
            if (event.target.matches('[data-quota-input]')) {
                onQuotaChange();
            }
        });

        // Enter di dalam input tidak boleh memuat ulang halaman; cukup simpan segera.
        settingForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (quotaDirty) saveQuotas();
        });
    }

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

    // Respons tambah/ubah/hapus hadiah membawa status undian terbaru.
    window.addEventListener('doorprize:state', (event) => render(event.detail));

    allocation.setPrizes(prizes);
    renderPrizeList(prizes);

    refresh();
    setInterval(refresh, 3000);
}
