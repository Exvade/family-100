@extends('layouts.app')

@section('title', 'Jawaban - ' . $question->question)
@section('page-pretitle', 'Family 100 • Kelola Acara')
@section('page-title', $question->question)

@section('page-actions')
    <a href="{{ route('family-100.questions.index') }}" class="btn" title="Kembali ke Daftar Pertanyaan" aria-label="Kembali ke Daftar Pertanyaan">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
        <span class="btn-label">Kembali</span>
    </a>
    <!-- Switch Mode Layar TV Panggung (Kuis <-> Hadiah) -->
    <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm gap-2">
        <span class="small fw-semibold text-secondary">Layar TV:</span>
        <span class="badge {{ $tvMode === 'gift' ? 'bg-warning text-dark' : 'bg-primary text-white' }} fw-bold d-inline-flex align-items-center gap-1" id="badgeTvMode">
            <span id="badgeTvModeIcon">
                @if ($tvMode === 'gift')
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                @endif
            </span>
            <span id="badgeTvModeText">{{ $tvMode === 'gift' ? 'HADIAH' : 'KUIS' }}</span>
        </span>
        <button type="button" class="btn btn-sm {{ $tvMode === 'gift' ? 'btn-outline-primary' : 'btn-outline-warning' }}" id="btnSwitchTvMode" data-url="{{ route('family-100.tv.mode') }}" title="Ganti mode layar TV antara Kuis dan Hadiah">
            {{ $tvMode === 'gift' ? 'Beralih ke TV Kuis' : 'Beralih ke TV Hadiah' }}
        </button>
    </div>
    <a href="{{ route('family-100.tv') }}" class="btn btn-cyan shadow-sm" target="_blank" rel="noopener" title="Buka Layar TV (Universal)" aria-label="Buka Layar TV">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
        <span class="btn-label">Buka Layar TV</span>
    </a>
    <button type="button" class="btn btn-primary shadow-sm" data-answer-form title="Tambah Jawaban" aria-label="Tambah Jawaban">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        <span class="btn-label">Tambah Jawaban</span>
    </button>
@endsection

@section('content')
    <!-- ========================================================
         MASTER TV STATUS & KONTROL CEPAT (SELALU TERLIHAT)
         ======================================================== -->
    <div class="card mb-3 shadow-sm border {{ $tvMode === 'gift' ? 'border-warning bg-warning-lt' : 'border-primary bg-primary-lt' }}" id="masterTvBar">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-2 {{ $tvMode === 'gift' ? 'bg-warning text-dark' : 'bg-primary text-white' }} shadow-sm d-flex align-items-center justify-content-center" id="masterTvIconContainer" style="width: 46px; height: 46px; flex: none;">
                        <span id="masterTvIcon" class="d-flex align-items-center justify-content-center">
                            @if ($tvMode === 'gift')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="26" height="26" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="26" height="26" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                            @endif
                        </span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge {{ $tvMode === 'gift' ? 'bg-warning text-dark' : 'bg-primary text-white' }} fs-3 fw-bold px-3 py-1 shadow-sm d-inline-flex align-items-center gap-2" id="masterTvBadge">
                                <span id="masterTvBadgeText">{{ $tvMode === 'gift' ? 'TV PANGGUNG: HADIAH' : 'TV PANGGUNG: KUIS' }}</span>
                            </span>
                            <span class="badge bg-success-lt text-success border border-success d-none d-md-inline-flex align-items-center gap-1">
                                <span class="status-dot status-dot-animated bg-success"></span>
                                Layar TV Tetap 1 Halaman (No Reload)
                            </span>
                        </div>
                        <div class="text-secondary small mt-1">
                            Layar TV panggung selalu berada di URL <code>/family-100/tv</code> tanpa perlu gonta-ganti halaman atau keluar fullscreen.
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn {{ $tvMode === 'gift' ? 'btn-primary' : 'btn-warning' }} fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="btnToggleTvFromMaster" data-url="{{ route('family-100.tv.mode') }}">
                        <span id="btnToggleTvIcon">
                            @if ($tvMode === 'gift')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                            @endif
                        </span>
                        <span id="btnToggleTvFromMasterText">
                            {{ $tvMode === 'gift' ? 'Alihkan Layar TV ke Kuis' : 'Alihkan Layar TV ke Hadiah' }}
                        </span>
                    </button>
                    <!-- Tombol Tes Audio Dashboard -->
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false" title="Tes suara kuis di laptop/komputer ini">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8a5 5 0 0 1 0 8" /><path d="M17.7 5a9 9 0 0 1 0 14" /><path d="M6 15h-2a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h2l3.5 -4.5a.8 .8 0 0 1 1.5 .5v14a.8 .8 0 0 1 -1.5 .5l-3.5 -4.5" /></svg>
                            <span>Tes Suara</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header text-uppercase text-secondary small fw-bold">Tes Sound Effect:</h6></li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2" id="btnTestCorrectSound">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="18" height="18" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                    <span>Tes Suara Benar (Chime)</span>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2" id="btnTestWrongSound">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="18" height="18" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                    <span>Tes Suara Salah (Buzzer)</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         TAB NAVIGASI UTAMA (BOLAK-BALIK KUIS & HADIAH DI DASHBOARD)
         ======================================================== -->
    <ul class="nav nav-pills nav-fill mb-3 bg-white p-2 rounded shadow-sm border gap-2" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold fs-3 py-2 px-3 d-flex align-items-center justify-content-center gap-2" id="tab-quiz-btn" data-bs-toggle="pill" data-bs-target="#tab-quiz-pane" type="button" role="tab" aria-controls="tab-quiz-pane" aria-selected="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M9 14l2 2l4 -4" /></svg>
                <span>Babak Kuis (Pertanyaan &amp; Jawaban)</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold fs-3 py-2 px-3 d-flex align-items-center justify-content-center gap-2" id="tab-gift-btn" data-bs-toggle="pill" data-bs-target="#tab-gift-pane" type="button" role="tab" aria-controls="tab-gift-pane" aria-selected="false">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                <span>Babak Hadiah Pemenang</span>
                <span class="badge bg-warning text-dark ms-1" id="badgeGiftCountTab">{{ $giftCount }} Kotak</span>
            </button>
        </li>
    </ul>

    <!-- ========================================================
         KONTEN DUA TAB
         ======================================================== -->
    <div class="tab-content" id="dashboardTabsContent">
        <!-- ====================================================
             TAB 1: KELOLA BABAK KUIS (PERTANYAAN & JAWABAN)
             ==================================================== -->
        <div class="tab-pane fade show active" id="tab-quiz-pane" role="tabpanel" aria-labelledby="tab-quiz-btn">
            <!-- BAR NAVIGASI PERTANYAAN (Sebelumnya, Pilih Pertanyaan, Selanjutnya) -->
            <div class="card mb-3 shadow-sm border bg-primary-lt">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <!-- Tombol Sebelumnya -->
                        <div>
                            @if ($prevQuestion)
                                <a href="{{ route('family-100.answers.index', $prevQuestion) }}" class="btn btn-outline-primary d-flex align-items-center gap-1 shadow-sm px-3" title="Ke: {{ $prevQuestion->question }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>
                                    <span class="fw-semibold">Sebelumnya</span>
                                </a>
                            @else
                                <button type="button" class="btn btn-outline-secondary opacity-50 px-3" disabled>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>
                                    <span>Sebelumnya</span>
                                </button>
                            @endif
                        </div>

                        <!-- Dropdown Pilih Pertanyaan Cepat & Status TV -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="dropdown">
                                <button type="button" class="btn btn-white dropdown-toggle fw-bold shadow-sm px-3" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih langsung nomor pertanyaan">
                                    <span class="badge bg-primary text-white me-2">Pertanyaan {{ $currentNumber }} / {{ $totalQuestions }}</span>
                                    <span class="d-none d-md-inline text-secondary small">Ganti Pertanyaan ▾</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-center shadow" style="max-height: 380px; overflow-y: auto; min-width: 320px;">
                                    <li class="dropdown-header text-uppercase text-secondary small fw-bold">Daftar Pertanyaan:</li>
                                    @foreach ($allQuestions as $idx => $q)
                                        @php $isCurrent = $q->id === $question->id; @endphp
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 {{ $isCurrent ? 'active fw-bold' : '' }}" href="{{ route('family-100.answers.index', $q) }}">
                                                <span class="text-truncate me-2" style="max-width: 250px;">{{ $idx + 1 }}. {{ $q->question }}</span>
                                                @if ($isCurrent)
                                                    <span class="badge bg-success text-white small ms-auto">Aktif di TV</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <span class="badge bg-success-lt text-success border border-success d-none d-md-inline-flex align-items-center gap-1 px-2 py-1" title="Layar TV otomatis berpindah ke pertanyaan ini">
                                <span class="status-dot status-dot-animated bg-success"></span>
                                Terhubung ke TV
                            </span>
                        </div>

                        <!-- Tombol Selanjutnya -->
                        <div>
                            @if ($nextQuestion)
                                <a href="{{ route('family-100.answers.index', $nextQuestion) }}" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm px-3 fw-bold" title="Ke: {{ $nextQuestion->question }}">
                                    <span>Selanjutnya</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                                </a>
                            @else
                                <button type="button" class="btn btn-outline-secondary opacity-50 px-3" disabled title="Sudah di pertanyaan terakhir">
                                    <span>Selanjutnya</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Informasi Pertanyaan & Kontrol TV (Strike & Reset) -->
            <div class="card mb-3 shadow-sm" style="border-left: 5px solid var(--tblr-primary) !important;">
                <div class="card-body p-3 p-md-4">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <span class="badge bg-success text-white text-uppercase fw-bold px-2 py-1" title="Pertanyaan ini sedang tampil di layar TV">
                                    <span class="status-dot status-dot-animated bg-white me-1"></span>
                                    Aktif di TV (No. {{ $currentNumber }})
                                </span>
                                <span class="badge bg-blue-lt">Maks. {{ $question->display_limit }} Jawaban di TV</span>
                                <span class="badge bg-secondary-lt">{{ $answers->count() }} Total Jawaban</span>
                            </div>
                            <div class="h2 fw-bold text-dark m-0 mb-2 lh-sm answer-question">
                                "{{ $question->question }}"
                            </div>
                            <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap">
                                <span>Hanya <strong>{{ $question->display_limit }} jawaban teratas</strong> (sesuai ranking) yang akan tampil di layar TV.</span>
                                <a href="{{ route('family-100.questions.edit', $question) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                                    Ubah Pertanyaan / Batas
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="text-secondary small text-uppercase fw-bold mb-1">Kontrol Efek TV</div>
                            <button type="button" class="btn btn-danger btn-lg px-3 px-md-4 py-3 shadow-sm w-100 mb-2 d-flex align-items-center justify-content-center gap-2" data-wrong-url="{{ route('family-100.questions.wrong', $question) }}" id="btn-strike">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="28" height="28" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <span class="fw-bold fs-3">Tombol Salah (Strike)</span>
                            </button>
                            <div class="d-flex align-items-center justify-content-between bg-danger-subtle text-danger border border-danger-subtle rounded px-3 py-2 mb-2">
                                <span class="fw-semibold small">Jumlah Salah di Layar TV:</span>
                                <span class="badge bg-danger text-white fs-4 fw-bold px-3 py-1"><span data-wrong-count-badge>{{ $question->wrong_count }}</span>x</span>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2 shadow-sm" data-reset-round-url="{{ route('family-100.questions.reset', $question) }}" id="btn-reset-round" title="Tutup kembali semua jawaban dan kembalikan salah ke 0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                                Reset Babak (Tutup Jawaban &amp; Salah)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Jawaban Kuis -->
            <div class="d-none d-md-block">
                <x-datatable title="Daftar Jawaban">
                    <thead>
                        <tr>
                            <th class="w-1"><button class="table-sort" data-dt-sort="no" data-dt-type="number">Nomor</button></th>
                            <th><button class="table-sort" data-dt-sort="name">Nama Jawaban</button></th>
                            <th><button class="table-sort" data-dt-sort="ranking" data-dt-type="number">Ranking</button></th>
                            <th class="w-1">Opsi Jawaban</th>
                            <th class="w-1">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($answers as $answer)
                            <tr data-row data-no="{{ $loop->iteration }}" data-name="{{ $answer->answer }}" data-ranking="{{ $answer->ranking }}">
                                <td><span class="text-secondary">{{ $loop->iteration }}</span></td>
                                <td>{{ $answer->answer }}</td>
                                <td>{{ $answer->ranking }}</td>
                                <td>
                                    @if ($onTvIds->contains($answer->id))
                                        <button type="button"
                                                class="btn {{ $answer->is_answered ? 'btn-orange' : 'btn-success' }}"
                                                data-answered-toggle="{{ route('family-100.answers.answered', $answer) }}">{{ $answer->is_answered ? 'Batalkan' : 'Terjawab' }}</button>
                                    @else
                                        <button type="button" class="btn btn-success" disabled title="Di luar batas {{ $question->display_limit }} jawaban yang tampil di TV">Terjawab</button>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-row-actions :delete-url="route('family-100.answers.destroy', $answer)" delete-message="Hapus jawaban ini?">
                                        <button type="button" class="dropdown-item" data-answer-form="{{ $answer->id }}">Edit</button>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>

            <div class="d-md-none">
                <livewire:answer-list :question="$question" />
            </div>

            <!-- Navigasi Bawah Halaman -->
            <div class="card mt-3 shadow-sm bg-body-tertiary">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            @if ($prevQuestion)
                                <a href="{{ route('family-100.answers.index', $prevQuestion) }}" class="btn btn-outline-secondary">
                                    ← {{ \Illuminate\Support\Str::limit($prevQuestion->question, 35) }}
                                </a>
                            @endif
                        </div>
                        <div>
                            @if ($nextQuestion)
                                <a href="{{ route('family-100.answers.index', $nextQuestion) }}" class="btn btn-primary fw-bold shadow-sm">
                                    Selanjutnya: {{ \Illuminate\Support\Str::limit($nextQuestion->question, 40) }} →
                                </a>
                            @else
                                <span class="text-secondary small fw-medium">Sudah di pertanyaan terakhir ({{ $totalQuestions }} dari {{ $totalQuestions }})</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================
             TAB 2: KELOLA KOTAK HADIAH (BONUS ROUND)
             ==================================================== -->
        <div class="tab-pane fade" id="tab-gift-pane" role="tabpanel" aria-labelledby="tab-gift-btn">
            <!-- Toolbar Pengaturan Jumlah & Reset Hadiah -->
            <div class="card shadow-sm border mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center g-3">
                        <div class="col-md-4">
                            <form action="{{ route('family-100.gifts.count') }}" method="POST" id="formGiftCount" class="d-flex align-items-center gap-2">
                                @csrf
                                <label class="form-label m-0 fw-semibold text-secondary text-nowrap" for="selectGiftCount">
                                    Jumlah Kotak Hadiah:
                                </label>
                                <select name="gift_count" id="selectGiftCount" class="form-select form-select-sm w-auto fw-bold" onchange="this.form.submit()">
                                    @for ($i = 5; $i <= 20; $i++)
                                        <option value="{{ $i }}" {{ $giftCount === $i ? 'selected' : '' }}>
                                            {{ $i }} Kotak {{ $i === 15 ? '(Default)' : '' }}
                                        </option>
                                    @endfor
                                </select>
                            </form>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="text-secondary small">
                                Kotak Hadiah Terbuka:
                                <span class="fw-bold text-success fs-3 align-middle" id="openedCountBadge">
                                    {{ $gifts->where('is_opened', true)->count() }}
                                </span>
                                <span class="text-muted">/ {{ $giftCount }} Kotak</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <button type="button" class="btn btn-warning text-dark btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnShuffleGiftsDirectTab" data-url="{{ route('family-100.gifts.shuffle') }}" title="Acak posisi atau urutan nomor seluruh hadiah yang ada">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 4l3 3l-3 3" /><path d="M18 20l3 -3l-3 -3" /><path d="M3 7h3a5 5 0 0 1 5 5a5 5 0 0 0 5 5h5" /><path d="M21 7h-5a4.978 4.978 0 0 0 -3 1.018m-4.004 7.964a4.978 4.978 0 0 1 -2.996 1.018h-3" /></svg>
                                <span>Acak Hadiah</span>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalBulkEditGifts" id="btnOpenBulkEditGiftsTab">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M9 4h10" /><path d="M14 8h5" /></svg>
                                <span>Edit Semua Hadiah (List)</span>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnResetAllGifts" data-url="{{ route('family-100.gifts.reset') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                                <span>Tutup Semua Kotak Hadiah</span>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnShowGiftsOnTvNow" data-url="{{ route('family-100.tv.mode') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
                                <span>Tampilkan Hadiah di TV</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Kartu Kotak Hadiah (1 s/d 15-20) -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3" id="giftsContainer">
                @foreach ($gifts as $gift)
                    <div class="col" data-gift-card="{{ $gift->id }}">
                        <div class="card h-100 shadow-sm border transition-all {{ $gift->is_opened ? 'border-success bg-success-lt' : 'border-warning-subtle bg-white' }}" style="border-radius: 12px;">
                            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center {{ $gift->is_opened ? 'bg-success text-white' : 'bg-warning-subtle text-dark' }}" style="border-radius: 12px 12px 0 0;">
                                <span class="badge {{ $gift->is_opened ? 'bg-white text-success' : 'bg-warning text-dark' }} fs-4 fw-bold px-2 py-1 shadow-sm">
                                    #{{ $gift->number }}
                                </span>
                                <span class="badge {{ $gift->is_opened ? 'bg-success-subtle text-success-emphasis border border-success' : 'bg-secondary-subtle text-secondary' }} px-2 py-1" data-gift-status-badge>
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
                                    @else
                                        <p class="text-secondary small mb-1 text-truncate" data-gift-desc style="display: none;"></p>
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
@endsection

@push('modals')
    <livewire:answer-form :question="$question" />

    <!-- Modal Edit Isi Hadiah -->
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
                        <input type="text" name="name" id="edit_gift_name" class="form-control" required placeholder="Contoh: Logam Mulia, Sepeda, Magic Com, Uang Tunai, dll.">
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
                    <button type="submit" class="btn btn-primary ms-auto" id="btnSubmitEditGift">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @include('family-100.gifts._bulk_modal', ['gifts' => $gifts, 'giftCount' => $giftCount])
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // SVG Template Helpers
    const svgTv = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>`;
    const svgTvLg = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="26" height="26" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>`;
    const svgGift = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
    const svgGiftLg = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="26" height="26" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>`;
    const svgPackage = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>`;

    // ========================================================
    // 1. KONTROL MODE TV PANGGUNG (KUIS <-> HADIAH)
    // ========================================================
    const updateTvModeUI = (tvMode) => {
        const badgeHeader = document.getElementById('badgeTvMode');
        const badgeHeaderIcon = document.getElementById('badgeTvModeIcon');
        const badgeHeaderText = document.getElementById('badgeTvModeText');
        const btnHeader = document.getElementById('btnSwitchTvMode');
        const masterBar = document.getElementById('masterTvBar');
        const masterIcon = document.getElementById('masterTvIcon');
        const masterIconContainer = document.getElementById('masterTvIconContainer');
        const masterBadge = document.getElementById('masterTvBadge');
        const masterBadgeText = document.getElementById('masterTvBadgeText');
        const masterBtn = document.getElementById('btnToggleTvFromMaster');
        const masterBtnIcon = document.getElementById('btnToggleTvIcon');
        const masterBtnText = document.getElementById('btnToggleTvFromMasterText');

        if (tvMode === 'gift') {
            if (badgeHeader) badgeHeader.className = 'badge bg-warning text-dark fw-bold d-inline-flex align-items-center gap-1';
            if (badgeHeaderIcon) badgeHeaderIcon.innerHTML = svgGift;
            if (badgeHeaderText) badgeHeaderText.textContent = 'HADIAH';
            if (btnHeader) {
                btnHeader.className = 'btn btn-sm btn-outline-primary';
                btnHeader.textContent = 'Beralih ke TV Kuis';
            }
            if (masterBar) masterBar.className = 'card mb-3 shadow-sm border border-warning bg-warning-lt';
            if (masterIconContainer) masterIconContainer.className = 'rounded-circle p-2 bg-warning text-dark shadow-sm d-flex align-items-center justify-content-center';
            if (masterIcon) masterIcon.innerHTML = svgGiftLg;
            if (masterBadge) masterBadge.className = 'badge bg-warning text-dark fs-3 fw-bold px-3 py-1 shadow-sm d-inline-flex align-items-center gap-2';
            if (masterBadgeText) masterBadgeText.textContent = 'TV PANGGUNG: HADIAH';
            if (masterBtn) masterBtn.className = 'btn btn-primary fw-bold shadow-sm d-inline-flex align-items-center gap-2';
            if (masterBtnIcon) masterBtnIcon.innerHTML = svgTv;
            if (masterBtnText) masterBtnText.textContent = 'Alihkan Layar TV ke Kuis';
        } else {
            if (badgeHeader) badgeHeader.className = 'badge bg-primary text-white fw-bold d-inline-flex align-items-center gap-1';
            if (badgeHeaderIcon) badgeHeaderIcon.innerHTML = svgTv;
            if (badgeHeaderText) badgeHeaderText.textContent = 'KUIS';
            if (btnHeader) {
                btnHeader.className = 'btn btn-sm btn-outline-warning';
                btnHeader.textContent = 'Beralih ke TV Hadiah';
            }
            if (masterBar) masterBar.className = 'card mb-3 shadow-sm border border-primary bg-primary-lt';
            if (masterIconContainer) masterIconContainer.className = 'rounded-circle p-2 bg-primary text-white shadow-sm d-flex align-items-center justify-content-center';
            if (masterIcon) masterIcon.innerHTML = svgTvLg;
            if (masterBadge) masterBadge.className = 'badge bg-primary text-white fs-3 fw-bold px-3 py-1 shadow-sm d-inline-flex align-items-center gap-2';
            if (masterBadgeText) masterBadgeText.textContent = 'TV PANGGUNG: KUIS';
            if (masterBtn) masterBtn.className = 'btn btn-warning fw-bold shadow-sm d-inline-flex align-items-center gap-2';
            if (masterBtnIcon) masterBtnIcon.innerHTML = svgGift;
            if (masterBtnText) masterBtnText.textContent = 'Alihkan Layar TV ke Hadiah';
        }
    };

    const handleSwitchTvMode = async (targetMode = null) => {
        const btnHeader = document.getElementById('btnSwitchTvMode');
        const masterBtn = document.getElementById('btnToggleTvFromMaster');
        if (btnHeader) btnHeader.disabled = true;
        if (masterBtn) masterBtn.disabled = true;

        try {
            const bodyData = targetMode ? { mode: targetMode } : {};
            const res = await fetch('{{ route("family-100.tv.mode") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(bodyData)
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            updateTvModeUI(data.tv_mode);

            // Beralih tab sesuai mode TV
            if (data.tv_mode === 'gift') {
                const tabGiftBtn = document.getElementById('tab-gift-btn');
                if (tabGiftBtn && typeof bootstrap !== 'undefined') {
                    const tab = new bootstrap.Tab(tabGiftBtn);
                    tab.show();
                }
            } else {
                const tabQuizBtn = document.getElementById('tab-quiz-btn');
                if (tabQuizBtn && typeof bootstrap !== 'undefined') {
                    const tab = new bootstrap.Tab(tabQuizBtn);
                    tab.show();
                }
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
            if (btnHeader) btnHeader.disabled = false;
            if (masterBtn) masterBtn.disabled = false;
        }
    };

    const btnSwitch = document.getElementById('btnSwitchTvMode');
    if (btnSwitch) btnSwitch.addEventListener('click', () => handleSwitchTvMode());

    const masterBtnToggle = document.getElementById('btnToggleTvFromMaster');
    if (masterBtnToggle) masterBtnToggle.addEventListener('click', () => handleSwitchTvMode());

    const btnShowGiftsNow = document.getElementById('btnShowGiftsOnTvNow');
    if (btnShowGiftsNow) btnShowGiftsNow.addEventListener('click', () => handleSwitchTvMode('gift'));

    // ========================================================
    // 2. KONTROL KOTAK HADIAH (TOGGLE, EDIT, RESET ALL)
    // ========================================================
    const updateOpenedGiftsCount = () => {
        const opened = Array.from(document.querySelectorAll('#giftsContainer [data-gift-status-badge]'))
            .filter(b => b.textContent.includes('TERBUKA')).length;
        const badge = document.getElementById('openedCountBadge');
        if (badge) badge.textContent = opened;
    };

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

                const cardCol = document.querySelector(`[data-gift-card="${data.gift_id}"]`);
                if (cardCol) {
                    const card = cardCol.querySelector('.card');
                    const header = card.querySelector('.card-header');
                    const numBadge = header.querySelector('.badge:first-child');
                    const statusBadge = cardCol.querySelector('[data-gift-status-badge]');
                    const iconContainer = cardCol.querySelector('[data-gift-icon-container]');
                    const iconEl = cardCol.querySelector('[data-gift-icon]');

                    if (data.is_opened) {
                        card.className = 'card h-100 shadow-sm border transition-all border-success bg-success-lt';
                        header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-success text-white';
                        numBadge.className = 'badge bg-white text-success fs-4 fw-bold px-2 py-1 shadow-sm';
                        statusBadge.className = 'badge bg-success-subtle text-success-emphasis border border-success px-2 py-1';
                        statusBadge.textContent = 'TERBUKA';
                        if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-success text-white shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                        if (iconEl) iconEl.innerHTML = svgGift;
                        btn.className = 'btn btn-sm btn-outline-secondary fw-bold w-100 shadow-sm';
                        btn.textContent = 'Tutup Kotak';
                    } else {
                        card.className = 'card h-100 shadow-sm border transition-all border-warning-subtle bg-white';
                        header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-warning-subtle text-dark';
                        numBadge.className = 'badge bg-warning text-dark fs-4 fw-bold px-2 py-1 shadow-sm';
                        statusBadge.className = 'badge bg-secondary-subtle text-secondary px-2 py-1';
                        statusBadge.textContent = 'TERTUTUP';
                        if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-warning-lt text-warning shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                        if (iconEl) iconEl.innerHTML = svgPackage;
                        btn.className = 'btn btn-sm btn-success fw-bold w-100 shadow-sm';
                        btn.textContent = 'Buka Kotak di TV';
                    }
                }

                updateOpenedGiftsCount();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Kotak #${data.number} ${data.is_opened ? 'dibuka di TV!' : 'ditutup kembali.'}`,
                    timer: 1500,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat mengubah status kotak hadiah.' });
            } finally {
                btn.disabled = false;
            }
        });
    });

    // Modal Edit Hadiah
    const modalEditGift = document.getElementById('modalEditGift');
    if (modalEditGift) {
        modalEditGift.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            const number = btn.dataset.number;
            const name = btn.dataset.name || '';
            const description = btn.dataset.description || '';
            const winner = btn.dataset.winner || '';
            const updateUrl = btn.dataset.updateUrl;

            document.getElementById('modalEditGiftTitle').textContent = `Edit Isi Hadiah #${number}`;
            document.getElementById('edit_gift_name').value = name;
            document.getElementById('edit_gift_description').value = description;
            document.getElementById('edit_gift_winner').value = winner;
            document.getElementById('formEditGift').action = updateUrl;
        });

        document.getElementById('formEditGift').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = document.getElementById('btnSubmitEditGift');
            submitBtn.disabled = true;

            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                // Update UI Kartu Hadiah
                const cardCol = document.querySelector(`[data-gift-card="${data.gift.id}"]`);
                if (cardCol) {
                    const nameEl = cardCol.querySelector('[data-gift-name]');
                    const descEl = cardCol.querySelector('[data-gift-desc]');
                    const winnerEl = cardCol.querySelector('[data-gift-winner]');
                    const editBtn = cardCol.querySelector('[data-bs-target="#modalEditGift"]');

                    if (nameEl) nameEl.textContent = data.gift.name || `Hadiah #${data.gift.number}`;
                    if (descEl) {
                        descEl.textContent = data.gift.description || '';
                        descEl.style.display = data.gift.description ? 'block' : 'none';
                    }
                    if (winnerEl) {
                        winnerEl.textContent = data.gift.winner_name ? `Pemenang: ${data.gift.winner_name}` : 'Belum ada pemenang';
                        winnerEl.className = data.gift.winner_name ? 'small text-primary fw-semibold' : 'small text-muted fst-italic';
                    }

                    if (editBtn) {
                        editBtn.dataset.name = data.gift.name || '';
                        editBtn.dataset.description = data.gift.description || '';
                        editBtn.dataset.winner = data.gift.winner_name || '';
                    }
                }

                bootstrap.Modal.getInstance(modalEditGift)?.hide();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menyimpan perubahan hadiah.' });
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    // Reset Semua Hadiah
    const btnResetAll = document.getElementById('btnResetAllGifts');
    if (btnResetAll) {
        btnResetAll.addEventListener('click', async () => {
            let isConfirmed = false;
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Tutup Semua Hadiah?',
                    text: 'Semua kotak hadiah akan ditutup kembali seperti semula di layar TV panggung.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tutup Semua',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d63939'
                });
                isConfirmed = result.isConfirmed;
            } else {
                isConfirmed = window.confirm('Semua kotak hadiah akan ditutup kembali seperti semula di layar TV panggung. Lanjutkan?');
            }

            if (!isConfirmed) return;
            btnResetAll.disabled = true;

            try {
                const res = await fetch(btnResetAll.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                // Reset kartu di DOM
                document.querySelectorAll('#giftsContainer [data-gift-card]').forEach(cardCol => {
                    const card = cardCol.querySelector('.card');
                    const header = card.querySelector('.card-header');
                    const numBadge = header.querySelector('.badge:first-child');
                    const statusBadge = cardCol.querySelector('[data-gift-status-badge]');
                    const iconContainer = cardCol.querySelector('[data-gift-icon-container]');
                    const iconEl = cardCol.querySelector('[data-gift-icon]');
                    const btn = cardCol.querySelector('[data-toggle-gift-btn]');

                    card.className = 'card h-100 shadow-sm border transition-all border-warning-subtle bg-white';
                    header.className = 'card-header py-2 px-3 d-flex justify-content-between align-items-center bg-warning-subtle text-dark';
                    numBadge.className = 'badge bg-warning text-dark fs-4 fw-bold px-2 py-1 shadow-sm';
                    statusBadge.className = 'badge bg-secondary-subtle text-secondary px-2 py-1';
                    statusBadge.textContent = 'TERTUTUP';
                    if (iconContainer) iconContainer.className = 'avatar avatar-md mx-auto mb-2 bg-warning-lt text-warning shadow-sm rounded-circle d-flex align-items-center justify-content-center';
                    if (iconEl) iconEl.innerHTML = svgPackage;
                    if (btn) {
                        btn.className = 'btn btn-sm btn-success fw-bold w-100 shadow-sm';
                        btn.textContent = 'Buka Kotak di TV';
                    }
                });

                updateOpenedGiftsCount();

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menutup semua kotak hadiah.' });
            } finally {
                btnResetAll.disabled = false;
            }
        });
    }

    // Acak Posisi Hadiah Langsung dari Toolbar Tab 2
    const btnShuffleDirectTab = document.getElementById('btnShuffleGiftsDirectTab');
    if (btnShuffleDirectTab) {
        btnShuffleDirectTab.addEventListener('click', async () => {
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

            btnShuffleDirectTab.disabled = true;
            try {
                const res = await fetch(btnShuffleDirectTab.dataset.url, {
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
                btnShuffleDirectTab.disabled = false;
            }
        });
    }

    // Submit Bulk Edit Hadiah via AJAX
    const formBulkAnswers = document.getElementById('formBulkEditGifts');
    if (formBulkAnswers) {
        formBulkAnswers.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById('btnSubmitBulkGifts');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.classList.add('btn-loading');
            }

            const giftsPayload = [];
            const nameInputs = formBulkAnswers.querySelectorAll('.bulk-gift-input-name');
            nameInputs.forEach((input, index) => {
                const idInput = formBulkAnswers.querySelector(`input[name="gifts[${index}][id]"]`);
                const descInput = formBulkAnswers.querySelector(`input[name="gifts[${index}][description]"]`);
                if (idInput && idInput.value) {
                    giftsPayload.push({
                        id: parseInt(idInput.value, 10),
                        name: input.value.trim(),
                        description: descInput ? descInput.value.trim() : ''
                    });
                }
            });

            try {
                const res = await fetch(formBulkAnswers.action, {
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

                // Update tampilan kartu di Tab Hadiah secara real-time
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
                formBulkAnswers.submit();
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('btn-loading');
                }
            }
        });
    }

    // ========================================================
    // 3. TES AUDIO DI DASHBOARD (Web Audio API Synthesizer)
    // ========================================================
    let dashAudioCtx = null;
    const getDashAudioCtx = () => {
        if (!dashAudioCtx) {
            const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
            dashAudioCtx = new AudioCtxClass();
        }
        if (dashAudioCtx.state === 'suspended') dashAudioCtx.resume();
        return dashAudioCtx;
    };

    const playTestChime = () => {
        try {
            const ctx = getDashAudioCtx();
            const now = ctx.currentTime;
            [659.25, 1046.50].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + i * 0.04);
                gain.gain.setValueAtTime(0.3, now + i * 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.04 + 0.8);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + i * 0.04);
                osc.stop(now + i * 0.04 + 0.8);
            });
            const a = new Audio('{{ asset("sounds/correct.mp3") }}');
            a.volume = 0.8;
            a.play().catch(() => {});
        } catch(e) {}
    };

    const playTestBuzzer = () => {
        try {
            const ctx = getDashAudioCtx();
            const now = ctx.currentTime;
            [130, 154].forEach(freq => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(freq, now);
                gain.gain.setValueAtTime(0.35, now);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.5);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.5);
            });
            const a = new Audio('{{ asset("sounds/wrong.mp3") }}');
            a.volume = 0.8;
            a.play().catch(() => {});
        } catch(e) {}
    };

    document.getElementById('btnTestCorrectSound')?.addEventListener('click', playTestChime);
    document.getElementById('btnTestWrongSound')?.addEventListener('click', playTestBuzzer);

    // ========================================================
    // SINKRONISASI REALTIME KOTAK HADIAH KE TV (Bidirectional Sync)
    // ========================================================
    const giftsTvStateUrl = '{{ route("family-100.gifts.tv.state") }}';
    let isPollingGiftsAnswers = false;

    async function syncGiftsStateWithTv() {
        if (isPollingGiftsAnswers) return;
        isPollingGiftsAnswers = true;

        try {
            const res = await fetch(giftsTvStateUrl, {
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
            isPollingGiftsAnswers = false;
        }
    }

    // Polling setiap 800ms
    setInterval(syncGiftsStateWithTv, 800);
});
</script>
@endpush
