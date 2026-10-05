// Tabel interaktif (search, sort, jumlah baris, pagination) untuk markup `[data-datatable]`.
// Baris <tr> membawa nilai lewat atribut data-*, header sort lewat data-dt-sort="<key>"
// (tambah data-dt-type="number" untuk urutan numerik).

const chevron = (d) => `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="${d}" /></svg>`;
const prevIcon = chevron('M15 6l-6 6l6 6');
const nextIcon = chevron('M9 6l6 6l-6 6');

function initDatatable(root) {
    const tbody = root.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
    const lengthInput = root.querySelector('[data-dt-length]');
    const searchInput = root.querySelector('[data-dt-search]');
    const pagination = root.querySelector('[data-dt-pagination]');
    const sortButtons = Array.from(root.querySelectorAll('[data-dt-sort]'));
    const numeric = new Set(sortButtons.filter((b) => b.dataset.dtType === 'number').map((b) => b.dataset.dtSort));

    const state = {
        page: 1,
        length: parseInt(lengthInput.value, 10) || 8,
        query: '',
        sortKey: sortButtons[0]?.dataset.dtSort,
        sortDir: 'asc',
    };

    const compare = (a, b) => {
        const key = state.sortKey;
        const result = numeric.has(key)
            ? Number(a.dataset[key]) - Number(b.dataset[key])
            : a.dataset[key].localeCompare(b.dataset[key], 'id', { sensitivity: 'base' });
        return state.sortDir === 'asc' ? result : -result;
    };

    const pageItem = (label, page, { active = false, disabled = false, html = false } = {}) => {
        const li = document.createElement('li');
        li.className = 'page-item' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        if (html) { a.innerHTML = label; } else { a.textContent = label; }
        a.addEventListener('click', (e) => {
            e.preventDefault();
            if (!disabled) { state.page = page; render(); }
        });
        li.appendChild(a);
        return li;
    };

    function render() {
        const q = state.query.trim().toLowerCase();
        const filtered = rows
            .filter((r) => !q || Object.values(r.dataset).some((v) => v.toLowerCase().includes(q)))
            .sort(compare);

        const pages = Math.max(1, Math.ceil(filtered.length / state.length));
        state.page = Math.min(state.page, pages);
        const start = (state.page - 1) * state.length;
        const visible = new Set(filtered.slice(start, start + state.length));

        rows.forEach((r) => r.classList.add('d-none'));
        filtered.forEach((r) => {
            tbody.appendChild(r);
            r.classList.toggle('d-none', !visible.has(r));
        });

        root.querySelector('[data-dt-from]').textContent = filtered.length ? start + 1 : 0;
        root.querySelector('[data-dt-to]').textContent = start + visible.size;
        root.querySelector('[data-dt-total]').textContent = filtered.length;

        pagination.replaceChildren(pageItem(prevIcon + ' prev', state.page - 1, { disabled: state.page === 1, html: true }));
        for (let i = 1; i <= pages; i++) {
            pagination.appendChild(pageItem(i, i, { active: i === state.page }));
        }
        pagination.appendChild(pageItem('next ' + nextIcon, state.page + 1, { disabled: state.page === pages, html: true }));

        sortButtons.forEach((b) => {
            b.classList.toggle('asc', b.dataset.dtSort === state.sortKey && state.sortDir === 'asc');
            b.classList.toggle('desc', b.dataset.dtSort === state.sortKey && state.sortDir === 'desc');
        });
    }

    lengthInput.addEventListener('input', () => {
        const n = parseInt(lengthInput.value, 10);
        if (n > 0) { state.length = n; state.page = 1; render(); }
    });
    searchInput.addEventListener('input', () => {
        state.query = searchInput.value; state.page = 1; render();
    });
    sortButtons.forEach((b) => b.addEventListener('click', () => {
        const key = b.dataset.dtSort;
        state.sortDir = state.sortKey === key && state.sortDir === 'asc' ? 'desc' : 'asc';
        state.sortKey = key;
        render();
    }));

    render();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datatable]').forEach(initDatatable);
});
