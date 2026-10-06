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

