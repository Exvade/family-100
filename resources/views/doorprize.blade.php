@extends('layouts.app')

@section('title', 'Doorprize')
@section('page-pretitle', 'Doorprize')
@section('page-title', 'Peserta & Pengundian')

@section('page-actions')
    <a href="{{ route('doorprize.tv') }}" class="btn btn-cyan" target="_blank" rel="noopener" title="Buka layar TV" aria-label="Buka layar TV">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
        <span class="btn-label">Buka TV ↗</span>
    </a>
    <a href="{{ route('doorprize.template') }}" class="btn" title="Unduh template Excel" aria-label="Unduh template Excel" data-category-template-link data-base-url="{{ route('doorprize.template') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
        <span class="btn-label">Template Excel</span>
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
                            SETTING LUCKY DRAW
                        </div>
                        <div class="small text-secondary">Atur kuota pemenang untuk tiap kategori tamu</div>
                    </div>
                    <span class="badge bg-primary text-white fs-6 px-2 py-1" id="quota-total-badge">{{ $quotaTotal }} Pemenang</span>
                </div>
                <div class="card-body p-3">
                    <form id="setting-lucky-draw-form" data-save-url="{{ route('doorprize.setting') }}">
                        <div class="list-group list-group-flush mb-3">
                            @foreach ($categories as $cat)
                                @php
                                    $displayLabel = \App\Models\Participant::displayLabel($cat);
                                    $quotaVal = $quotaSetting[$cat] ?? 1;
                                    $eligible = $eligibleByCategory[$cat] ?? 0;
                                @endphp
                                <div class="list-group-item d-flex align-items-center justify-content-between px-1 py-2">
                                    <div class="me-2">
                                        <div class="fw-bold text-dark">{{ $displayLabel }}</div>
                                        <div class="small text-secondary">
                                            Tersedia: <span class="badge bg-secondary-lt" data-quota-eligible="{{ $cat }}">{{ $eligible }}</span> peserta
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

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 text-uppercase shadow-sm" id="btn-save-lucky-draw">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                            [ SIMPAN SETTING ]
                        </button>
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
                 data-configure-url="{{ route('doorprize.spin.configure') }}"
                 data-status="{{ $spin['status'] }}"
                 data-reset-url="{{ route('doorprize.spin.reset') }}"
                 data-won="{{ $spin['won'] }}"
                 data-eligible="{{ $spin['eligible'] }}"
                 data-eligible-total="{{ $eligibleTotal }}"
                 data-duration-url="{{ route('doorprize.spin.duration') }}"
                 data-slots="{{ $slots }}"
                 data-categories="{{ json_encode($spin['categories'] ?? []) }}"
                 data-all-categories="{{ json_encode($categories) }}"
                 data-eligible-by-category="{{ json_encode($eligibleByCategory) }}"
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
                            Putar Undian Sesuai Kuota (<span data-trigger-quota-total>{{ $quotaTotal }}</span> Pemenang)
                        </button>
                    </div>

                    <!-- TRIGGER CEPAT PER KATEGORI (Permintaan: "di db nya ada kategori buat triger") -->
                    <div class="p-3 bg-body-tertiary rounded border mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-bold small text-uppercase text-secondary m-0">
                                Trigger Cepat per Kategori
                            </label>
                            <span class="small text-muted">1 klik langsung putar undian kategori terkait</span>
                        </div>
                        <div class="row g-2">
                            @foreach ($categories as $cat)
                                @php
                                    $displayLabel = \App\Models\Participant::displayLabel($cat);
                                    $eligible = $eligibleByCategory[$cat] ?? 0;
                                    $color = match($cat) {
                                        'Keluarga CPP' => 'indigo',
                                        'Keluarga CPW' => 'pink',
                                        'Teman CPW' => 'purple',
                                        'Teman CPP' => 'cyan',
                                        default => 'azure',
                                    };
                                @endphp
                                <div class="col-6 col-sm-4 col-md-auto flex-fill">
                                    <button type="button" class="btn btn-outline-{{ $color }} w-100 d-flex flex-column align-items-center py-2" data-trigger-category="{{ $cat }}" title="Putar undian untuk {{ $displayLabel }}">
                                        <span class="fw-bold">{{ $displayLabel }}</span>
                                        <span class="small opacity-75 mt-1" data-trigger-cat-count="{{ $cat }}">({{ $eligible }} peserta)</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- TRIGGER KUSTOM: BEBAS PILIH SLOT & KATEGORI -->
                    <div class="accordion mb-3" id="accordionCustomTrigger">
                        <div class="accordion-item border">
                            <h2 class="accordion-header" id="headingCustom">
                                <button class="accordion-button collapsed py-2 px-3 fw-semibold text-secondary small text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom" aria-expanded="false" aria-controls="collapseCustom">
                                    Opsi Trigger Kustom (Slot 1 s/d 10 & Filter Kategori Bebas)
                                </button>
                            </h2>
                            <div id="collapseCustom" class="accordion-collapse collapse" aria-labelledby="headingCustom">
                                <div class="accordion-body p-3 bg-body-tertiary">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-5">
                                            <label class="form-label fw-bold small text-uppercase text-secondary mb-2">
                                                Jumlah Pemenang (1 s/d 10)
                                            </label>
                                            <div class="btn-group w-100" role="group">
                                                @for ($s = 1; $s <= 10; $s++)
                                                    <button type="button" class="btn {{ $slots === $s ? 'btn-primary' : 'btn-outline-secondary' }}" data-spin-slot="{{ $s }}">
                                                        {{ $s }}
                                                    </button>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-7">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <label class="form-label fw-bold small text-uppercase text-secondary m-0">
                                                    Kategori yang Diundi
                                                </label>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" data-spin-all-categories>
                                                    Pilih Semua
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach ($categories as $cat)
                                                    @php
                                                        $catCount = $eligibleByCategory[$cat] ?? 0;
                                                        $isChecked = empty($spin['categories']) || in_array($cat, $spin['categories']);
                                                    @endphp
                                                    <label class="form-selectgroup-item flex-fill">
                                                        <input type="checkbox" name="spin_categories" value="{{ $cat }}" class="form-selectgroup-input" data-spin-category {{ $isChecked ? 'checked' : '' }}>
                                                        <span class="form-selectgroup-label d-flex align-items-center justify-content-between px-2 py-1">
                                                            <span class="small fw-semibold">{{ \App\Models\Participant::displayLabel($cat) }}</span>
                                                            <span class="badge bg-secondary-lt ms-2" data-cat-count>{{ $catCount }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-success w-100 fw-bold" data-spin-action="start">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 4v16l13 -8z" /></svg>
                                            Putar Undian Kustom (<span data-custom-slots-label>{{ $slots }}</span> Pemenang)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ol class="spin-winners" data-spin-winners hidden></ol>

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
                            {{ $cat }} <span class="badge bg-{{ $badgeColor }}-lt ms-1">{{ $count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Desktop: datatable seperti halaman Family 100 --}}
    <div class="d-none d-md-block">
        <x-datatable title="Daftar Peserta">
            <thead>
                <tr>
                    <th class="w-1"><button class="table-sort" data-dt-sort="no" data-dt-type="number">Nomor</button></th>
                    <th><button class="table-sort" data-dt-sort="name">Nama Peserta</button></th>
                    <th><button class="table-sort" data-dt-sort="category">Kategori</button></th>
                    <th><button class="table-sort" data-dt-sort="status">Status</button></th>
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
                    <tr data-row data-no="{{ $loop->iteration }}" data-name="{{ $participant->name }}" data-category="{{ $participant->category }}" data-status="{{ $participant->isWinner() ? 'PEMENANG' : '' }}">
                        <td><span class="text-secondary">{{ $loop->iteration }}</span></td>
                        <td class="fw-medium">{{ $participant->name }}</td>
                        <td>
                            <span class="badge bg-{{ $badgeColor }}-lt">{{ $participant->category }}</span>
                        </td>
                        <td data-col-status>
                            @if ($participant->isWinner())
                                <span class="badge bg-green-lt" title="Terpilih {{ $participant->won_at->translatedFormat('d M Y H:i') }}">PEMENANG</span>
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
                        <td colspan="5" class="text-center text-secondary py-4">Belum ada peserta. Tambah manual atau unggah file Excel.</td>
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
    <livewire:participant-form />
    <livewire:participant-import />
@endpush
