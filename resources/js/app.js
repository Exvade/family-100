import './datatable';
import './flash';
import './answered';
import './timer';

// Pastikan dropdown di dalam table-responsive tidak terpotong saat dibuka
document.addEventListener('show.bs.dropdown', (e) => {
    const tableResponsive = e.target.closest('.table-responsive');
    if (tableResponsive) {
        tableResponsive.style.overflow = 'visible';
    }
});

document.addEventListener('hidden.bs.dropdown', (e) => {
    const tableResponsive = e.target.closest('.table-responsive');
    if (tableResponsive) {
        tableResponsive.style.overflow = '';
    }
});

// Tutup dropdown jika user melakukan scroll (baik scroll halaman maupun scroll tabel horizontal)
window.addEventListener('scroll', () => {
    document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
        const toggle = menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
        if (toggle) {
            const bs = window.bootstrap;
            if (bs?.Dropdown) {
                const instance = bs.Dropdown.getInstance(toggle);
                if (instance) {
                    instance.hide();
                    return;
                }
            }
            menu.classList.remove('show');
            toggle.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
            const tableResponsive = menu.closest('.table-responsive');
            if (tableResponsive) {
                tableResponsive.style.overflow = '';
            }
        }
    });
}, true);

