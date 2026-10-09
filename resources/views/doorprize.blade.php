@extends('layouts.app')

@section('title', 'Doorprize')
@section('page-pretitle', 'Doorprize')
@section('page-title', 'Peserta & Pengundian')

@section('page-actions')
    <a href="{{ route('doorprize.tv') }}" class="btn btn-cyan" target="_blank" rel="noopener" title="Buka layar TV" aria-label="Buka layar TV">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
        <span class="btn-label">Buka TV ↗</span>
    </a>
    <div class="dropdown">
        <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Unduh template Excel" aria-label="Unduh template Excel">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
            <span class="btn-label">Template Excel</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow-sm">
            <a class="dropdown-item fw-bold" href="{{ route('doorprize.template') }}" data-category-template-link data-base-url="{{ route('doorprize.template') }}">
                Template Multi-Sheet (5 Kategori)
            </a>
            <div class="dropdown-divider"></div>
            <div class="dropdown-header text-uppercase small text-secondary">Template Per Kategori (Hanya Nama):</div>
            @foreach ($categories as $cat)
                <a class="dropdown-item" href="{{ route('doorprize.template', ['category' => $cat]) }}">
                    {{ \App\Models\Participant::displayLabel($cat) }}
                </a>
            @endforeach
        </div>
    </div>
    <a href="{{ route('doorprize.winners.export') }}" class="btn btn-outline-success" id="btn-export-winners" title="Unduh Daftar Pemenang beserta Hadiahnya (.xlsx)" {{ $spin['won'] < 1 ? 'hidden' : '' }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
        <span class="btn-label">Unduh Pemenang</span>
    </a>
    <button type="button" class="btn" data-participant-import data-active-category-btn title="Upload Excel" aria-label="Upload Excel">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" /></svg>
        <span class="btn-label">Upload Excel</span>
    </button>
    <button type="button" class="btn btn-primary" data-participant-form data-active-category-btn title="Tambah Peserta" aria-label="Tambah Peserta">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        <span class="btn-label">Tambah Peserta</span>
    </button>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        <!-- 1. SETTING LUCKY DRAW (Formulir Setting Kuota Pemenang per Kategori sesuai WhatsApp) -->
        <div class="col-12 col-xl-5">
            <div class="card h-100 shadow-sm border-primary" id="lucky-draw-setting-card">
                <div class="card-header bg-primary-lt d-flex align-items-center justify-content-between py-2">
                    <div>
                        <div class="text-uppercase fw-bold text-primary small">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a9 9 0 0 1 9 9v1a9 9 0 0 1 -9 9a9 9 0 0 1 -9 -9v-1a9 9 0 0 1 9 -9z" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M12 3v6" /><path d="M12 15v6" /><path d="M3 12h6" /><path d="M15 12h6" /></svg>
                            Pengaturan Kuota Tiap Kategori
                        </div>
                        <div class="small text-secondary">Atur kuota pemenang untuk tiap kategori tamu</div>
                    </div>
                    <span class="badge bg-primary text-white fs-6 px-2 py-1" id="quota-total-badge">{{ $quotaTotal }} Pemenang</span>
                </div>
                <div class="card-body p-3">
                    <form id="setting-lucky-draw-form" autocomplete="off" data-save-url="{{ route('doorprize.setting') }}">
                        <div class="list-group list-group-flush mb-3">
                            @foreach ($categories as $cat)
                                @php
                                    $displayLabel = \App\Models\Participant::displayLabel($cat);
                                    $quotaVal = $quotaSetting[$cat] ?? 0;
                                    $eligible = $eligibleByCategory[$cat] ?? 0;
                                    $leftDiff = $eligible - $quotaVal;
                                    $hasParticipants = ($eligibleByCategory[$cat] ?? 0) > 0;
                                @endphp
                                {{-- Baris kategori tanpa peserta yang belum menang disembunyikan; muncul lagi otomatis saat ada peserta (mis. setelah Reset Pemenang). --}}
                                <div class="list-group-item align-items-center justify-content-between px-1 py-2 {{ $hasParticipants ? 'd-flex' : 'd-none' }}" data-quota-row="{{ $cat }}">
                                    <div class="me-2">
                                        <div class="fw-bold text-dark">{{ $displayLabel }}</div>
                                        <div class="small text-secondary">
                                            Tersedia: <span class="badge bg-secondary-lt" data-quota-eligible="{{ $cat }}">{{ $eligible }}</span> peserta
                                            <span class="{{ $leftDiff < 0 ? 'text-danger fw-semibold' : 'text-secondary' }}" data-quota-left="{{ $cat }}">· {{ $leftDiff < 0 ? 'Kurang '.(-$leftDiff).' peserta' : 'Belum masuk kuota: '.$leftDiff }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="input-group input-group-sm" style="width: 125px;">
                                            <button type="button" class="btn btn-outline-secondary" data-quota-step="-1" data-cat="{{ $cat }}">−</button>
                                            <input type="number" name="quotas[{{ $cat }}]" value="{{ $quotaVal }}" min="0" max="10" class="form-control text-center fw-bold fs-5" data-quota-input data-cat="{{ $cat }}">
                                            <button type="button" class="btn btn-outline-secondary" data-quota-step="1" data-cat="{{ $cat }}">+</button>
                                        </div>
                                        <span class="text-secondary small fw-medium text-nowrap">Pemenang</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-2 bg-body-tertiary rounded border mb-3">
                            <span class="fw-bold fs-4 text-dark">Total</span>
                            <span class="fw-bold fs-4 text-primary"><span id="quota-total-display">{{ $quotaTotal }}</span> Pemenang</span>
                        </div>
                        <div class="small text-center text-secondary mb-2" id="quota-left-total" aria-live="polite">
                            Peserta belum masuk kuota: <strong>{{ collect($categories)->sum(fn ($c) => max(0, ($eligibleByCategory[$c] ?? 0) - ($quotaSetting[$c] ?? 0))) }}</strong>
                        </div>

                        <div class="small text-center text-secondary" id="quota-save-status" aria-live="polite">Perubahan kuota tersimpan otomatis.</div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. KONTROL & TRIGGER UNDIAN (Pemicu Undian di Layar TV) -->
        <div class="col-12 col-xl-7">
            <div class="card h-100 spin-control" id="spin-control"
                 data-state-url="{{ route('doorprize.tv.state') }}"
                 data-start-url="{{ route('doorprize.spin.start') }}"
                 data-stop-url="{{ route('doorprize.spin.stop') }}"
                 data-status="{{ $spin['status'] }}"
                 data-reset-url="{{ route('doorprize.spin.reset') }}"
                 data-won="{{ $spin['won'] }}"
                 data-eligible="{{ $spin['eligible'] }}"
                 data-eligible-total="{{ $eligibleTotal }}"
                 data-duration-url="{{ route('doorprize.spin.duration') }}"
                 data-eligible-by-category="{{ json_encode($eligibleByCategory) }}"
                 data-quota-setting="{{ json_encode($quotaSetting) }}"
                 data-prizes="{{ json_encode($prizes) }}"
                 data-quota-total="{{ $quotaTotal }}">
                <div class="card-body p-3">
                    <div class="spin-control-head mb-3">
                        <div class="spin-control-info">
                            <div class="text-secondary small text-uppercase fw-bold">Kontrol & Trigger Undian Doorprize</div>
                            <div class="h3 m-0" data-spin-status aria-live="polite"></div>
                            <div class="text-secondary small" data-spin-hint></div>
                        </div>
                        <div class="spin-control-actions">
                            <button type="button" class="btn btn-danger btn-lg" data-spin-action="stop">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 5m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /></svg>
                                Stop
                            </button>
                        </div>
                    </div>

                    <!-- ALOKASI HADIAH: slot ke-n mendapat hadiah ke-n -->
                    <div class="p-3 bg-body-tertiary rounded border mb-3" data-allocation>
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <div class="fw-bold small text-uppercase text-secondary d-flex align-items-center gap-1">
                                    <span>🎁</span> Hadiah yang Diundi
                                </div>
                                <div class="small text-muted">Pemenang di slot ke-1 mendapat hadiah baris pertama, dan seterusnya.</div>
                            </div>
                            <span class="badge bg-secondary-lt fs-6 px-2 py-1 text-nowrap" data-allocation-progress>0 / 0 slot</span>
                        </div>

                        <div class="allocation-list" data-allocation-rows></div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-allocation-add>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                Tambah baris hadiah
                            </button>
                            <span class="small text-secondary text-end" data-allocation-summary></span>
                        </div>
                        <div class="form-hint mt-2">Kosongkan jumlah pada satu baris untuk memakai sisa slot.</div>
                    </div>

                    <!-- TRIGGER UTAMA: SESUAI SETTING KUOTA -->
                    <div class="p-3 bg-primary-lt rounded border border-primary mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="badge bg-primary text-white text-uppercase">Trigger Utama</span>
                                <div class="fw-bold fs-4 text-primary mt-1">Undi Sesuai Setting Kuota (<span data-trigger-quota-total>{{ $quotaTotal }}</span> Pemenang)</div>
                                <div class="small text-secondary">Memilih pemenang otomatis sesuai kuota tiap kategori yang disimpan.</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" data-trigger-action="start-quota">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8z" /></svg>
                            Putar Undian Sesuai Kuota (<span data-trigger-quota-total>{{ $quotaTotal }}</span>&nbsp;Pemenang)
                        </button>
                        <div class="small text-danger mt-2" data-trigger-quota-note aria-live="polite" hidden></div>
                    </div>

                    <!-- TRIGGER CEPAT PER KATEGORI (Permintaan: "di db nya ada kategori buat triger") -->
                    <div class="p-3 bg-body-tertiary rounded border mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-bold small text-uppercase text-secondary m-0">
                                Trigger Cepat per Kategori
                            </label>
                            <span class="small text-muted">1 klik langsung putar undian sesuai kuota kategori</span>
                        </div>
                        <div class="row g-2">
                            @foreach ($categories as $cat)
                                @php
                                    $displayLabel = \App\Models\Participant::displayLabel($cat);
                                    $quota = $quotaSetting[$cat] ?? 0;
                                    $catEligible = $eligibleByCategory[$cat] ?? 0;
                                    // Keterangan di tombol; harus sama dengan logika di doorprize-control.js
                                    $catNote = match (true) {
                                        $quota < 1 => 'Kuota 0, atur dulu di Pengaturan Kuota',
                                        $catEligible < 1 => 'Semua peserta sudah menang / belum ada peserta',
                                        $catEligible < $quota => "Peserta kurang: butuh {$quota}, tersisa {$catEligible}",
                                        $spin['status'] === 'spinning' => 'Undian sedang berputar',
                                        default => "({$quota} pemenang)",
                                    };
                                    $color = match($cat) {
                                        'Keluarga CPP' => 'indigo',
                                        'Keluarga CPW' => 'pink',
                                        'Teman CPW' => 'purple',
                                        'Teman CPP' => 'cyan',
                                        default => 'azure',
                                    };
                                @endphp
                                <div class="col-6 col-sm-4 col-md-auto flex-fill {{ ($eligibleByCategory[$cat] ?? 0) > 0 ? '' : 'd-none' }}" data-trigger-col="{{ $cat }}">
                                    <button type="button" class="btn btn-outline-{{ $color }} w-100 d-flex flex-column align-items-center py-2" data-trigger-category="{{ $cat }}" title="Putar undian {{ $quota }} pemenang dari {{ $displayLabel }}">
                                        <span class="fw-bold">{{ $displayLabel }}</span>
                                        <span class="small opacity-75 mt-1 text-center" data-trigger-cat-count="{{ $cat }}">{{ $catNote }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="spin-footer">
                        <div class="spin-duration">
                            <label for="spin-duration-input" class="form-label m-0">Durasi spin otomatis</label>
                            <div class="input-group spin-duration-field">
                                <input type="number" id="spin-duration-input" class="form-control" min="0" max="{{ $maxDuration }}" step="1" inputmode="numeric" value="{{ $spin['duration'] }}" data-spin-duration>
                                <span class="input-group-text">detik</span>
                                <button type="button" class="btn" data-spin-duration-save>Simpan</button>
                            </div>
                            <div class="form-hint m-0">0 = berputar sampai tombol Stop diklik.</div>
                        </div>

                        <div class="spin-reset">
                            <button type="button" class="btn btn-outline-danger" data-spin-reset>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                                Reset Pemenang
                            </button>
                            <div class="form-hint m-0" data-spin-reset-hint></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD DAFTAR HADIAH -->
    <div class="card mb-3 shadow-sm" id="prize-card"
         data-store-url="{{ route('doorprize.prizes.store') }}"
         data-item-url="{{ url('/doorprize/hadiah') }}">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <div>
                <h3 class="card-title fw-bold m-0 d-flex align-items-center gap-1">
                    <span>🎁</span> Daftar Hadiah
                </h3>
                <div class="text-secondary small">Pilih hadiah di panel kontrol sebelum memutar. Stok berkurang otomatis untuk tiap pemenang.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white fs-6 px-2 py-1" id="prize-count-badge">{{ count($prizes) }} Hadiah</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#prize-modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                    Tambah Hadiah
                </button>
            </div>
        </div>
        <div class="prize-scroll">
            <table class="table table-vcenter table-striped card-table">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th class="w-1">No</th>
                        <th class="w-1">Gambar</th>
                        <th>Nama Hadiah</th>
                        <th class="w-1">Jumlah</th>
                        <th>Terundi</th>
                        <th>Pemenang</th>
                        <th class="w-1">Opsi</th>
                    </tr>
                </thead>
                <tbody id="prize-tbody"></tbody>
            </table>
        </div>
        {{-- Satu pemilih berkas dipakai bersama oleh semua kolom Gambar di tabel --}}
        <input type="file" id="prize-image-input" accept="image/jpeg,image/png,image/webp" hidden>
    </div>

    <div class="alert alert-info py-2 px-3 small mb-3" role="alert">
        Nama peserta di sini dipakai halaman <a href="{{ route('doorprize.tv') }}" class="alert-link" target="_blank" rel="noopener">layar TV doorprize</a>.
        Buka halaman TV dulu sebelum menekan Start. TV akan memperbarui daftar peserta dan kategori secara otomatis saat Start ditekan.
    </div>

    {{-- Tabs Kategori Peserta (Bisa langsung lihat, tambah & import per kategori) --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-dt-category="">
                        Semua Kategori <span class="badge bg-secondary-lt ms-1">{{ $participants->count() }}</span>
                    </a>
                </li>
                @foreach ($categories as $cat)
                    @php
                        $badgeColor = match($cat) {
                            'Keluarga CPP' => 'indigo',
                            'Keluarga CPW' => 'pink',
                            'Teman CPP' => 'cyan',
                            'Teman CPW' => 'purple',
                            default => 'azure',
                        };
                        $count = $participants->where('category', $cat)->count();
                    @endphp
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-dt-category="{{ $cat }}">
                            {{ \App\Models\Participant::displayLabel($cat) }} <span class="badge bg-{{ $badgeColor }}-lt ms-1">{{ $count }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="nav-item ms-md-auto">
                    <a href="#" class="nav-link text-success fw-bold" data-dt-category="PEMENANG" id="tab-winners-history" title="Lihat hanya peserta yang sudah menang">
                        🏆 Riwayat Pemenang <span class="badge bg-success text-white ms-1" id="tab-winners-count">{{ $spin['won'] }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Desktop: datatable seperti halaman Family 100 --}}
    <div class="d-none d-md-block">
        <x-datatable title="Daftar Peserta" :data-category-labels="json_encode(collect($categories)->mapWithKeys(fn ($c) => [$c => \App\Models\Participant::displayLabel($c)]))">
            <thead>
                <tr>
                    <th class="w-1"><button class="table-sort" data-dt-sort="no" data-dt-type="number">Nomor</button></th>
                    <th><button class="table-sort" data-dt-sort="name">Nama Peserta</button></th>
                    <th><button class="table-sort" data-dt-sort="category">Kategori</button></th>
                    <th><button class="table-sort" data-dt-sort="status">Status</button></th>
                    <th><button class="table-sort" data-dt-sort="wonAt">Waktu Menang</button></th>
                    <th class="w-1">Opsi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($participants as $participant)
                    @php
                        $badgeColor = match($participant->category) {
                            'Keluarga CPP' => 'indigo',
                            'Keluarga CPW' => 'pink',
                            'Teman CPP' => 'cyan',
                            'Teman CPW' => 'purple',
                            default => 'azure',
                        };
                    @endphp
                    <tr data-row data-no="{{ $loop->iteration }}" data-name="{{ $participant->name }}" data-category="{{ $participant->category }}" data-category-label="{{ \App\Models\Participant::displayLabel($participant->category) }}" data-status="{{ $participant->isWinner() ? 'PEMENANG' : '' }}" data-won-at="{{ $participant->won_at?->toIso8601String() }}" data-update-url="{{ route('doorprize.participants.update', $participant) }}">
                        <td><span class="text-secondary">{{ $loop->iteration }}</span></td>
                        <td class="fw-medium inline-editable" data-inline="name" title="Klik untuk mengubah nama">{{ $participant->name }}</td>
                        <td class="inline-editable" data-inline="category" title="Klik untuk mengubah kategori">
                            <span class="badge bg-{{ $badgeColor }}-lt">{{ \App\Models\Participant::displayLabel($participant->category) }}</span>
                        </td>
                        <td data-col-status class="inline-editable" data-inline="status" title="Klik untuk mengubah status">
                            @if ($participant->isWinner())
                                <span class="badge bg-green-lt" title="Terpilih {{ $participant->won_at->translatedFormat('d M Y H:i') }}">PEMENANG</span>
                            @endif
                        </td>
                        <td data-col-won-at class="text-secondary small">
                            @if ($participant->isWinner())
                                <span title="{{ $participant->won_at->translatedFormat('d M Y, H:i') }}">{{ $participant->won_at->diffForHumans() }}</span>
                                <span class="d-block text-muted" style="font-size: 0.75rem;">{{ $participant->won_at->translatedFormat('d M Y, H:i') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <x-row-actions :delete-url="route('doorprize.participants.destroy', $participant)" delete-message="Hapus peserta ini?">
                                <button type="button" class="dropdown-item" data-participant-form="{{ $participant->id }}" data-category="{{ $participant->category }}">Edit</button>
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Belum ada peserta. Tambah manual atau unggah file Excel.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-datatable>
    </div>

    {{-- Mobile: kartu dengan infinite scroll (Livewire) --}}
    <div class="d-md-none">
        <livewire:participant-list />
    </div>
@endsection

@push('modals')
    <div class="modal modal-blur fade" id="prize-modal" tabindex="-1" aria-labelledby="prize-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" data-prize-form autocomplete="off" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="prize-modal-title">Tambah Hadiah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required" for="prize-name-input">Nama hadiah</label>
                        <input type="text" id="prize-name-input" name="name" class="form-control" maxlength="255" placeholder="mis. Sepeda Motor" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label required" for="prize-qty-input">Jumlah</label>
                            <input type="number" id="prize-qty-input" name="quantity" class="form-control" min="1" max="{{ \App\Models\Prize::MAX_QUANTITY }}" value="1" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label" for="prize-image-new">Gambar (opsional)</label>
                            <input type="file" id="prize-image-new" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-hint">JPG, PNG, atau WEBP, maksimal 2 MB.</div>
                        </div>
                    </div>
                    <div class="text-danger small mt-3" data-prize-form-error hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Hadiah</button>
                </div>
            </form>
        </div>
    </div>
    <livewire:participant-form />
    <livewire:participant-import />
@endpush
