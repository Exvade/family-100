// Isi sel "Waktu Menang" pada baris tabel peserta (`[data-col-won-at]`); `info` berisi won_at, won_at_human, won_at_formatted
// seperti pada riwayat pemenang. Kirim null untuk mengosongkan.
export function renderWonAt(row, info) {
    row.dataset.wonAt = info?.won_at ?? '';

    const cell = row.querySelector('[data-col-won-at]');
    if (!cell) return;

    if (!info?.won_at) {
        cell.replaceChildren();
        return;
    }

    const human = document.createElement('span');
    human.title = info.won_at_formatted || '';
    human.textContent = info.won_at_human || '';

    const formatted = document.createElement('span');
    formatted.className = 'd-block text-muted';
    formatted.style.fontSize = '0.75rem';
    formatted.textContent = info.won_at_formatted || '';

    cell.replaceChildren(human, formatted);
}
