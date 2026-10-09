import Swal from 'sweetalert2';

// Tombol "Terjawab": menandai jawaban muncul/tidak di layar TV tanpa reload halaman.
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-answered-toggle]');
    if (!button || button.disabled) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(button.dataset.answeredToggle, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const { answered } = await response.json();
        button.classList.toggle('btn-orange', answered);
        button.classList.toggle('btn-success', !answered);
        button.textContent = answered ? 'Batalkan' : 'Terjawab';
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Status jawaban tidak bisa diubah. Coba lagi.' });
    } finally {
        button.disabled = false;
    }
});

// Tombol "Salah": Mengirim sinyal strike ke TV (menampilkan X dan buzzer) dan menambah penghitung salah
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-wrong-url]');
    if (!button || button.disabled) {
        return;
    }

    button.disabled = true;
    button.classList.add('opacity-75');

    try {
        const response = await fetch(button.dataset.wrongUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const badge = document.querySelector('[data-wrong-count-badge]');
        if (badge && data.wrong_count !== undefined) {
            badge.textContent = data.wrong_count;
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tanda salah tidak terkirim ke TV. Coba lagi.' });
    } finally {
        // Debounce singkat 350ms agar tombol tetap responsif tanpa terkena double-click ketidaksengajaan
        setTimeout(() => {
            button.disabled = false;
            button.classList.remove('opacity-75');
        }, 350);
    }
});

// Tombol "Reset Babak": menutup kembali semua jawaban dan mereset hitungan salah ke 0
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-reset-round-url]');
    if (!button || button.disabled) {
        return;
    }

    const { isConfirmed } = await Swal.fire({
        title: 'Reset Babak Ini?',
        text: 'Semua jawaban pada babak ini akan ditutup kembali dan hitungan salah akan direset ke 0.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d63939',
        focusCancel: true,
    });

    if (!isConfirmed) return;

    button.disabled = true;

    try {
        const response = await fetch(button.dataset.resetRoundUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        // Reset tombol jawaban di tabel ke hijau "Terjawab"
        document.querySelectorAll('[data-answered-toggle]').forEach((btn) => {
            btn.classList.remove('btn-orange');
            btn.classList.add('btn-success');
            btn.textContent = 'Terjawab';
        });

        // Reset badge salah ke 0
        const badge = document.querySelector('[data-wrong-count-badge]');
        if (badge) {
            badge.textContent = data.wrong_count ?? '0';
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: data.message || 'Babak berhasil direset!',
            timer: 1500,
            showConfirmButton: false,
        });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mereset babak. Coba lagi.' });
    } finally {
        button.disabled = false;
    }
});
