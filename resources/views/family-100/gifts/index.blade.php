@extends('layouts.app')

@section('title', 'Kelola Hadiah Kuis - Family 100')

@section('content')
<div class="row g-3">
    <!-- Header Bar -->
    <div class="col-12">
        <div class="card bg-primary-lt border-primary-subtle shadow-sm">
            <div class="card-body py-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="avatar avatar-md bg-warning text-dark shadow-sm rounded-circle d-flex align-items-center justify-content-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                            </span>
                            <h2 class="card-title m-0 fw-bold fs-2 text-primary">Kelola Hadiah Kuis (Bonus Round)</h2>
                        </div>
                        <p class="text-secondary small m-0">
                            Atur kotak hadiah 3D, buka hadiah untuk pemenang, dan kontrol layar TV panggung.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                        <!-- Kontrol Mode TV Panggung -->
                        <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm gap-2">
                            <span class="small fw-semibold text-secondary">Layar TV Panggung:</span>
                            <span class="badge {{ $tvMode === 'gift' ? 'bg-warning text-dark' : 'bg-primary text-white' }} fw-bold d-inline-flex align-items-center gap-1" id="tvModeStatusBadge">
                                <span id="tvModeIcon">
                                    @if ($tvMode === 'gift')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                                    @endif
                                </span>
                                <span id="tvModeStatusText">{{ $tvMode === 'gift' ? 'HADIAH' : 'KUIS' }}</span>
                            </span>
                            <button type="button" class="btn btn-sm {{ $tvMode === 'gift' ? 'btn-outline-primary' : 'btn-outline-warning' }}" id="btnSwitchTvMode" data-url="{{ route('family-100.tv.mode') }}">
                                {{ $tvMode === 'gift' ? 'Beralih ke TV Kuis' : 'Beralih ke TV Hadiah' }}
                            </button>
                        </div>

                        <!-- Link Buka TV di Tab Terpisah -->
                        <a href="{{ route('family-100.gifts.tv') }}" target="_blank" class="btn btn-warning shadow-sm" title="Buka layar TV Hadiah di tab atau monitor terpisah">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                            Buka TV Hadiah ↗
                        </a>
                        <a href="{{ route('family-100.tv') }}" target="_blank" class="btn btn-outline-primary shadow-sm" title="Buka layar TV Kuis utama">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                            Buka TV Kuis ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Pengaturan Jumlah & Reset -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body py-2">
                <div class="row align-items-center g-3">
                    <div class="col-md-6 col-lg-4">
                        <form action="{{ route('family-100.gifts.count') }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf
                            <label class="form-label m-0 fw-semibold text-secondary text-nowrap" for="gift_count">
                                Jumlah Kotak Hadiah:
                            </label>
                            <select name="gift_count" id="gift_count" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                @for ($i = 5; $i <= 30; $i++)
                                    <option value="{{ $i }}" {{ $giftCount === $i ? 'selected' : '' }}>
                                        {{ $i }} Kotak {{ $i === 15 ? '(Default)' : '' }}
                                    </option>
                                @endfor
                            </select>
                        </form>
                    </div>
                    <div class="col-md-6 col-lg-4 text-center">
                        <div class="text-secondary small">
                            Kotak Terbuka:
                            <span class="fw-bold text-success fs-4 align-middle" id="openedCountBadge">
                                {{ $gifts->where('is_opened', true)->count() }}
                            </span>
                            <span class="text-muted">/ {{ $giftCount }} Kotak</span>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnShuffleGiftsDirect" data-url="{{ route('family-100.gifts.shuffle') }}" title="Acak urutan nomor seluruh hadiah yang ada">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 4l3 3l-3 3" /><path d="M18 20l3 -3l-3 -3" /><path d="M3 7h3a5 5 0 0 1 5 5a5 5 0 0 0 5 5h5" /><path d="M21 7h-5a4.978 4.978 0 0 0 -3 1.018m-4.004 7.964a4.978 4.978 0 0 1 -2.996 1.018h-3" /></svg>
                            <span>Acak Hadiah</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalBulkEditGifts" id="btnOpenBulkEditGifts">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M9 4h10" /><path d="M14 8h5" /></svg>
                            <span>Edit Semua Hadiah (List)</span>
                        </button>
                        <form action="{{ route('family-100.gifts.reset') }}" method="POST" id="formResetGifts" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnResetGifts">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                                <span>Tutup Semua Kotak Hadiah</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Kartu Kotak Hadiah -->
    <div class="col-12">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 row-cols-xl-6 g-3" id="giftsContainer">
            @foreach ($gifts as $gift)
                <div class="col" data-gift-card="{{ $gift->id }}">
                    <div class="card h-100 shadow-sm border transition-all {{ $gift->is_opened ? 'border-success bg-success-lt' : 'border-warning-subtle bg-white' }}" style="border-radius: 12px;">
                        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center {{ $gift->is_opened ? 'bg-success text-white' : 'bg-warning-subtle text-dark' }}" style="border-radius: 12px 12px 0 0;">
                            <span class="badge {{ $gift->is_opened ? 'bg-white text-success' : 'bg-warning text-dark' }} fs-4 fw-bold px-2 py-1 shadow-sm">
                                #{{ $gift->number }}
                            </span>
                            <span class="badge {{ $gift->is_opened ? 'bg-secondary-subtle text-secondary' : 'bg-secondary-subtle text-secondary' }} px-2 py-1" data-gift-status-badge>
                                {{ $gift->is_opened ? 'TERBUKA' : 'TERTUTUP' }}
                            </span>
                        </div>
                        <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                            <div class="mb-3">
                                <div class="avatar avatar-md mx-auto mb-2 {{ $gift->is_opened ? 'bg-success text-white' : 'bg-warning-lt text-warning' }} shadow-sm rounded-circle d-flex align-items-center justify-content-center" data-gift-icon-container style="width: 44px; height: 44px;">
                                    <span data-gift-icon class="d-flex align-items-center justify-content-center">
                                        @if ($gift->is_opened)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>
                                        @endif
                                    </span>
                                </div>
                                <h3 class="fw-bold mb-1 text-truncate" title="{{ $gift->name }}" data-gift-name>
                                    {{ $gift->name ?: "Hadiah #{$gift->number}" }}
                                </h3>
                                @if ($gift->description)
                                    <p class="text-secondary small mb-1 text-truncate" data-gift-desc title="{{ $gift->description }}">
                                        {{ $gift->description }}
                                    </p>
                                @endif
                                <div class="small {{ $gift->winner_name ? 'text-primary fw-semibold' : 'text-muted fst-italic' }}" data-gift-winner>
                                    {{ $gift->winner_name ? 'Pemenang: ' . $gift->winner_name : 'Belum ada pemenang' }}
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-1">
                                <button type="button" class="btn btn-sm {{ $gift->is_opened ? 'btn-outline-secondary' : 'btn-success' }} fw-bold w-100 shadow-sm" data-toggle-gift-btn="{{ $gift->id }}" data-toggle-url="{{ route('family-100.gifts.toggle', $gift) }}">
                                    {{ $gift->is_opened ? 'Tutup Kotak' : 'Buka Kotak di TV' }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalEditGift" data-id="{{ $gift->id }}" data-number="{{ $gift->number }}" data-name="{{ $gift->name }}" data-description="{{ $gift->description }}" data-winner="{{ $gift->winner_name }}" data-update-url="{{ route('family-100.gifts.update', $gift) }}">
                                    Edit Isi Hadiah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal Edit Hadiah -->
<div class="modal modal-blur fade" id="modalEditGift" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="formEditGift" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalEditGiftTitle">Edit Hadiah #</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Nama / Isi Hadiah</label>
                    <input type="text" name="name" id="edit_gift_name" class="form-control" required placeholder="Contoh: Sepeda Lipat, Uang Rp 1.000.000, Setrika, dll.">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi / Keterangan (Opsional)</label>
                    <textarea name="description" id="edit_gift_description" class="form-control" rows="2" placeholder="Keterangan tambahan jika ada..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Pemenang (Opsional)</label>
                    <input type="text" name="winner_name" id="edit_gift_winner" class="form-control" placeholder="Nama tamu atau keluarga yang memilih kotak ini">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary ms-auto">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@include('family-100.gifts._bulk_modal', ['gifts' => $gifts, 'giftCount' => $giftCount])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Toggle Buka / Tutup Hadiah via AJAX
    document.querySelectorAll('[data-toggle-gift-btn]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            btn.disabled = true;

            try {
                const res = await fetch(btn.dataset.toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                // Update tampilan kartu hadiah
                const cardCol = document.querySelector(`[data-gift-card="${data.gift_id}"]`);
                if (cardCol) {
                    const card = cardCol.querySelector('.card');
                    const header = card.querySelector('.card-header');
                    const numBadge = header.querySelector('.badge:first-child');
                    const statusBadge = cardCol.querySelector('[data-gift-status-badge]');
                    const iconContainer = cardCol.querySelector('[data-gift-icon-container]');
                    const icon = cardCol.querySelector('[data-gift-icon]');

                    const svgGiftIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
                    const svgPackageIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>`;

                    if (data.is_opened) {
                        card.className = 'card h-100 shadow-sm border transition-all border-success bg-success-lt';
                        header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-success text-white';
                        numBadge.className = 'badge bg-white text-success fs-4 fw-bold px-2 py-1 shadow-sm';
                        statusBadge.className = 'badge bg-success-subtle text-success-emphasis border border-success px-2 py-1';
                        statusBadge.textContent = 'TERBUKA';
                        if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-success text-white shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                        if (icon) icon.innerHTML = svgGiftIcon;
                        btn.className = 'btn btn-sm btn-outline-secondary fw-bold w-100 shadow-sm';
                        btn.textContent = 'Tutup Kotak';
                    } else {
                        card.className = 'card h-100 shadow-sm border transition-all border-warning-subtle bg-white';
                        header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-warning-subtle text-dark';
                        numBadge.className = 'badge bg-warning text-dark fs-4 fw-bold px-2 py-1 shadow-sm';
                        statusBadge.className = 'badge bg-secondary-subtle text-secondary px-2 py-1';
                        statusBadge.textContent = 'TERTUTUP';
                        if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-warning-lt text-warning shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                        if (icon) icon.innerHTML = svgPackageIcon;
                        btn.className = 'btn btn-sm btn-success fw-bold w-100 shadow-sm';
                        btn.textContent = 'Buka Kotak di TV';
                    }
                }

                // Update opened count badge
                const openedTotal = document.querySelectorAll('[data-gift-status-badge]').length;
                const openedCount = Array.from(document.querySelectorAll('[data-gift-status-badge]')).filter(b => b.textContent.includes('TERBUKA')).length;
                const countBadge = document.getElementById('openedCountBadge');
                if (countBadge) countBadge.textContent = openedCount;

            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memperbarui status hadiah di server.' });
            } finally {
                btn.disabled = false;
            }
        });
    });

    // Switch Mode TV Panggung (Quiz <-> Gift)
    const btnSwitchTv = document.getElementById('btnSwitchTvMode');
    if (btnSwitchTv) {
        btnSwitchTv.addEventListener('click', async () => {
            btnSwitchTv.disabled = true;
            try {
                const res = await fetch(btnSwitchTv.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                const statusBadge = document.getElementById('tvModeStatusBadge');
                const tvModeIcon = document.getElementById('tvModeIcon');
                const tvModeStatusText = document.getElementById('tvModeStatusText');
                if (data.tv_mode === 'gift') {
                    statusBadge.className = 'badge bg-warning text-dark fw-bold d-inline-flex align-items-center gap-1';
                    if (tvModeIcon) tvModeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
                    if (tvModeStatusText) tvModeStatusText.textContent = 'HADIAH';
                    btnSwitchTv.className = 'btn btn-sm btn-outline-primary';
                    btnSwitchTv.textContent = 'Beralih ke TV Kuis';
                } else {
                    statusBadge.className = 'badge bg-primary text-white fw-bold d-inline-flex align-items-center gap-1';
                    if (tvModeIcon) tvModeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>`;
                    if (tvModeStatusText) tvModeStatusText.textContent = 'KUIS';
                    btnSwitchTv.className = 'btn btn-sm btn-outline-warning';
                    btnSwitchTv.textContent = 'Beralih ke TV Hadiah';
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengubah mode layar TV.' });
            } finally {
                btnSwitchTv.disabled = false;
            }
        });
    }

    // Modal Edit Hadiah population
    const modalEdit = document.getElementById('modalEditGift');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', (e) => {
            const trigger = e.relatedTarget;
            if (!trigger) return;

            const form = document.getElementById('formEditGift');
            form.action = trigger.dataset.updateUrl;

            document.getElementById('modalEditGiftTitle').textContent = `Edit Hadiah #${trigger.dataset.number}`;
            document.getElementById('edit_gift_name').value = trigger.dataset.name || '';
            document.getElementById('edit_gift_description').value = trigger.dataset.description || '';
            document.getElementById('edit_gift_winner').value = trigger.dataset.winner || '';
        });
    }

    // Konfirmasi Reset Semua Hadiah
    const btnReset = document.getElementById('btnResetGifts');
    if (btnReset) {
        btnReset.addEventListener('click', async (e) => {
            e.preventDefault();

            let isConfirmed = false;
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Tutup Semua Hadiah?',
                    text: 'Seluruh kotak hadiah akan direset tertutup kembali untuk babak baru.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d63939',
                    confirmButtonText: 'Ya, Tutup Semua',
                    cancelButtonText: 'Batal'
                });
                isConfirmed = result.isConfirmed;
            } else {
                isConfirmed = window.confirm('Seluruh kotak hadiah akan direset tertutup kembali untuk babak baru. Lanjutkan?');
            }

            if (!isConfirmed) return;

            btnReset.disabled = true;

            try {
                const res = await fetch('{{ route("family-100.gifts.reset") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                // Reset kartu hadiah di DOM
                document.querySelectorAll('#giftsContainer [data-gift-card]').forEach(cardCol => {
                    const card = cardCol.querySelector('.card');
                    const header = card?.querySelector('.card-header');
                    const numBadge = header?.querySelector('.badge:first-child');
                    const statusBadge = cardCol.querySelector('[data-gift-status-badge]');
                    const iconContainer = cardCol.querySelector('[data-gift-icon-container]');
                    const iconEl = cardCol.querySelector('[data-gift-icon]');
                    const btnToggle = cardCol.querySelector('[data-toggle-gift-btn]');

                    if (card) card.className = 'card h-100 shadow-sm border transition-all border-warning-subtle bg-white';
                    if (header) header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-warning-subtle text-dark';
                    if (numBadge) numBadge.className = 'badge bg-warning text-dark fs-4 fw-bold px-2 py-1 shadow-sm';
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-secondary-subtle text-secondary px-2 py-1';
                        statusBadge.textContent = 'TERTUTUP';
                    }
                    if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-warning-lt text-warning shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                    if (iconEl) {
                        iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>`;
                    }
                    if (btnToggle) {
                        btnToggle.className = 'btn btn-sm btn-success fw-bold w-100 shadow-sm';
                        btnToggle.textContent = 'Buka Kotak di TV';
                    }
                });

                const countBadge = document.getElementById('openedCountBadge');
                if (countBadge) countBadge.textContent = '0';

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message || 'Semua kotak hadiah berhasil ditutup kembali.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } catch (err) {
                const form = document.getElementById('formResetGifts');
                if (form) {
                    form.submit();
                } else if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menutup semua kotak hadiah.' });
                }
            } finally {
                btnReset.disabled = false;
            }
        });
    }

    // Acak Posisi Hadiah Langsung dari Toolbar
    const btnShuffleDirect = document.getElementById('btnShuffleGiftsDirect');
    if (btnShuffleDirect) {
        btnShuffleDirect.addEventListener('click', async () => {
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Acak Posisi Hadiah Tertutup?',
                    text: 'Hanya kotak hadiah yang BELUM dibuka yang akan diacak posisinya. Hadiah yang sudah terbuka tidak akan ikut diacak.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Acak Sekarang',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
            }

            btnShuffleDirect.disabled = true;
            try {
                const res = await fetch(btnShuffleDirect.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                if (Array.isArray(data.gifts)) {
                    data.gifts.forEach(g => {
                        const cardCol = document.querySelector(`[data-gift-card="${g.id}"]`);
                        if (cardCol) {
                            const nameEl = cardCol.querySelector('[data-gift-name]');
                            if (nameEl) {
                                nameEl.textContent = g.name || `Hadiah #${g.number}`;
                                nameEl.title = g.name || `Hadiah #${g.number}`;
                            }

                            let descEl = cardCol.querySelector('[data-gift-desc]');
                            if (g.description) {
                                if (!descEl) {
                                    descEl = document.createElement('p');
                                    descEl.className = 'text-secondary small mb-1 text-truncate';
                                    descEl.setAttribute('data-gift-desc', '');
                                    const winnerEl = cardCol.querySelector('[data-gift-winner]');
                                    if (winnerEl) winnerEl.parentNode.insertBefore(descEl, winnerEl);
                                }
                                descEl.textContent = g.description;
                                descEl.title = g.description;
                                descEl.style.display = '';
                            } else if (descEl) {
                                descEl.textContent = '';
                                descEl.style.display = 'none';
                            }

                            const editBtn = cardCol.querySelector('[data-bs-target="#modalEditGift"]');
                            if (editBtn) {
                                editBtn.dataset.name = g.name;
                                editBtn.dataset.description = g.description || '';
                            }

                            // Animasi kartu HANYA untuk kotak yang belum dibuka
                            if (!g.is_opened) {
                                const card = cardCol.querySelector('.card');
                                if (card) {
                                    card.style.transition = 'all 0.35s ease';
                                    card.style.transform = 'scale(1.03)';
                                    card.style.boxShadow = '0 0 12px rgba(245, 158, 11, 0.4)';
                                    setTimeout(() => {
                                        card.style.transform = '';
                                        card.style.boxShadow = '';
                                    }, 600);
                                }
                            }
                        }
                    });
                }

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: data.status === 'warning' ? 'warning' : 'success',
                        title: data.message || 'Posisi hadiah berhasil diacak!',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengacak posisi hadiah.' });
                }
            } finally {
                btnShuffleDirect.disabled = false;
            }
        });
    }

    // Submit Bulk Edit Hadiah via AJAX
    const formBulk = document.getElementById('formBulkEditGifts');
    if (formBulk) {
        formBulk.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById('btnSubmitBulkGifts');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.classList.add('btn-loading');
            }

            const giftsPayload = [];
            const nameInputs = formBulk.querySelectorAll('.bulk-gift-input-name');
            nameInputs.forEach((input, index) => {
                const idInput = formBulk.querySelector(`input[name="gifts[${index}][id]"]`);
                const descInput = formBulk.querySelector(`input[name="gifts[${index}][description]"]`);
                if (idInput && idInput.value) {
                    giftsPayload.push({
                        id: parseInt(idInput.value, 10),
                        name: input.value.trim(),
                        description: descInput ? descInput.value.trim() : ''
                    });
                }
            });

            try {
                const res = await fetch(formBulk.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ gifts: giftsPayload })
                });

                if (!res.ok) {
                    const errJson = await res.json().catch(() => null);
                    throw new Error(errJson?.message || 'HTTP ' + res.status);
                }

                const data = await res.json();

                // Update tampilan kartu di dashboard secara real-time
                giftsPayload.forEach(g => {
                    const cardCol = document.querySelector(`[data-gift-card="${g.id}"]`);
                    if (cardCol) {
                        const nameEl = cardCol.querySelector('[data-gift-name]');
                        if (nameEl) {
                            nameEl.textContent = g.name || `Hadiah #${g.id}`;
                            nameEl.title = g.name || `Hadiah #${g.id}`;
                        }

                        let descEl = cardCol.querySelector('[data-gift-desc]');
                        if (g.description) {
                            if (!descEl) {
                                descEl = document.createElement('p');
                                descEl.className = 'text-secondary small mb-1 text-truncate';
                                descEl.setAttribute('data-gift-desc', '');
                                const winnerEl = cardCol.querySelector('[data-gift-winner]');
                                if (winnerEl) winnerEl.parentNode.insertBefore(descEl, winnerEl);
                            }
                            descEl.textContent = g.description;
                            descEl.title = g.description;
                            descEl.style.display = '';
                        } else if (descEl) {
                            descEl.textContent = '';
                            descEl.style.display = 'none';
                        }

                        // Sinkronkan data-attribute pada tombol edit satuan
                        const editBtn = cardCol.querySelector('[data-bs-target="#modalEditGift"]');
                        if (editBtn) {
                            editBtn.dataset.name = g.name;
                            editBtn.dataset.description = g.description || '';
                        }
                    }
                });

                // Tutup modal
                const modalEl = document.getElementById('modalBulkEditGifts');
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                }

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message || 'Semua hadiah berhasil disimpan!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } catch (err) {
                // Fallback submit bila AJAX terkendala
                formBulk.submit();
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('btn-loading');
                }
            }
        });
    }

    // ========================================================
    // SINKRONISASI REALTIME KE LAYAR TV (Bidirectional Sync)
    // ========================================================
    const tvStateUrl = '{{ route("family-100.gifts.tv.state") }}';
    let isPollingGifts = false;

    async function syncGiftsStateWithTv() {
        if (isPollingGifts) return;
        isPollingGifts = true;

        try {
            const res = await fetch(tvStateUrl, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            // 1. Sinkronkan jumlah kotak terbuka di badge
            const countBadge = document.getElementById('openedCountBadge');
            if (countBadge && data.opened_count !== undefined) {
                countBadge.textContent = data.opened_count;
            }

            // 2. Sinkronkan tiap kartu hadiah
            (data.gifts || []).forEach(g => {
                const cardCol = document.querySelector(`[data-gift-card="${g.id}"]`);
                if (!cardCol) return;

                const card = cardCol.querySelector('.card');
                const header = card?.querySelector('.card-header');
                const numBadge = header?.querySelector('.badge:first-child');
                const statusBadge = cardCol.querySelector('[data-gift-status-badge]');
                const iconContainer = cardCol.querySelector('[data-gift-icon-container]');
                const iconEl = cardCol.querySelector('[data-gift-icon]');
                const btnToggle = cardCol.querySelector('[data-toggle-gift-btn]');

                const isOpened = Boolean(g.is_opened);
                const currentStatus = statusBadge?.textContent.trim();

                const svgGift = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
                const svgPackage = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>`;

                if (isOpened && currentStatus !== 'TERBUKA') {
                    if (card) card.className = 'card h-100 shadow-sm border transition-all border-success bg-success-lt';
                    if (header) header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-success text-white';
                    if (numBadge) numBadge.className = 'badge bg-white text-success fs-4 fw-bold px-2 py-1 shadow-sm';
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-success-subtle text-success-emphasis border border-success px-2 py-1';
                        statusBadge.textContent = 'TERBUKA';
                    }
                    if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-success text-white shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                    if (iconEl) iconEl.innerHTML = svgGift;
                    if (btnToggle) {
                        btnToggle.className = 'btn btn-sm btn-outline-secondary fw-bold w-100 shadow-sm';
                        btnToggle.textContent = 'Tutup Kotak';
                    }
                } else if (!isOpened && currentStatus !== 'TERTUTUP') {
                    if (card) card.className = 'card h-100 shadow-sm border transition-all border-warning-subtle bg-white';
                    if (header) header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-warning-subtle text-dark';
                    if (numBadge) numBadge.className = 'badge bg-warning text-dark fs-4 fw-bold px-2 py-1 shadow-sm';
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-secondary-subtle text-secondary px-2 py-1';
                        statusBadge.textContent = 'TERTUTUP';
                    }
                    if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-warning-lt text-warning shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                    if (iconEl) iconEl.innerHTML = svgPackage;
                    if (btnToggle) {
                        btnToggle.className = 'btn btn-sm btn-success fw-bold w-100 shadow-sm';
                        btnToggle.textContent = 'Buka Kotak di TV';
                    }
                }

                // Update nama pemenang jika ada perubahan
                const winnerEl = cardCol.querySelector('[data-gift-winner]');
                if (winnerEl) {
                    if (g.winner_name) {
                        winnerEl.className = 'small text-primary fw-semibold';
                        winnerEl.textContent = 'Pemenang: ' + g.winner_name;
                    } else if (winnerEl.textContent.startsWith('Pemenang:')) {
                        winnerEl.className = 'small text-muted fst-italic';
                        winnerEl.textContent = 'Belum ada pemenang';
                    }
                }
            });
        } catch (e) {
            // Silently ignore network hiccup
        } finally {
            isPollingGifts = false;
        }
    }

    // Polling setiap 800ms
    setInterval(syncGiftsStateWithTv, 800);
});
</script>
@endpush
