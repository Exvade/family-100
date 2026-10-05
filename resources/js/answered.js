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

// Tombol "Salah": TV menampilkan X dan buzzer. Setelah diklik, tombol menunggu 5 detik (server juga membatasi).
const WRONG_COOLDOWN_SECONDS = 5;

function cooldown(button, seconds) {
    const label = button.dataset.label ?? button.textContent;
    button.dataset.label = label;
    button.disabled = true;

    let left = seconds;
    button.textContent = `${label} (${left})`;

    const timer = setInterval(() => {
        left -= 1;
        if (left <= 0) {
            clearInterval(timer);
            button.textContent = label;
            button.disabled = false;
        } else {
            button.textContent = `${label} (${left})`;
        }
    }, 1000);
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-wrong-url]');
    if (!button || button.disabled) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(button.dataset.wrongUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (response.status === 429) {
            // Sudah ditekan baru-baru ini (mungkin dari tab lain): ikuti sisa waktu dari server.
            cooldown(button, Number(response.headers.get('Retry-After')) || WRONG_COOLDOWN_SECONDS);
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        cooldown(button, WRONG_COOLDOWN_SECONDS);
    } catch (error) {
        button.disabled = false;
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tanda salah tidak terkirim ke TV. Coba lagi.' });
    }
});
