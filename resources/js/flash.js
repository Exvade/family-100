import Swal from 'sweetalert2';

// Menampilkan pesan hasil aksi (session 'status') sebagai SweetAlert.
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (!flash) {
        return;
    }

    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: flash.dataset.flash,
        timer: 2500,
        timerProgressBar: true,
        showConfirmButton: false,
    });
});
