import Swal from 'sweetalert2';

// Panel timer di halaman Jawaban: Mulai/Jeda dan Reset. Tampilan TV mengikuti lewat polling.
const format = (ms) => {
    const seconds = Math.ceil(ms / 1000);
    return String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
};

function initTimer(root) {
    const display = root.querySelector('[data-timer-display]');
    const toggle = root.querySelector('[data-timer-toggle]');
    const reset = root.querySelector('[data-timer-reset]');
    const timer = { ...JSON.parse(root.dataset.state), receivedAt: performance.now() };

    const remaining = () => (timer.status === 'running'
        ? Math.max(0, timer.remaining_ms - (performance.now() - timer.receivedAt))
        : timer.remaining_ms);

    function render() {
        const left = remaining();
        const finished = timer.status === 'finished' || (timer.status === 'running' && left === 0);
        const running = timer.status === 'running' && !finished;

        display.textContent = format(left);
        display.classList.toggle('text-red', finished);
        display.classList.toggle('text-orange', !finished && running && left <= 10000);
        toggle.textContent = running ? 'Jeda' : 'Mulai';
        toggle.classList.toggle('btn-orange', running);
        toggle.classList.toggle('btn-success', !running);
    }

    async function send(url) {
        [toggle, reset].forEach((b) => { b.disabled = true; });

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            Object.assign(timer, await response.json(), { receivedAt: performance.now() });
            render();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Timer tidak bisa diubah. Coba lagi.' });
        } finally {
            [toggle, reset].forEach((b) => { b.disabled = false; });
        }
    }

    toggle.addEventListener('click', () => {
        const running = timer.status === 'running' && remaining() > 0;
        send(running ? toggle.dataset.pauseUrl : toggle.dataset.startUrl);
    });
    reset.addEventListener('click', () => send(reset.dataset.url));

    render();
    setInterval(render, 200);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-timer]').forEach(initTimer);
});
