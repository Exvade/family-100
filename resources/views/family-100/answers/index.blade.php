@extends('layouts.app')

@section('title', 'Jawaban - ' . $question->question)
@section('page-pretitle', 'Family 100 • Kelola Jawaban')
@section('page-title', $question->question)

@section('page-actions')
    <a href="{{ route('family-100.questions.index') }}" class="btn" title="Kembali ke Daftar Pertanyaan" aria-label="Kembali ke Daftar Pertanyaan">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
        <span class="btn-label">Kembali</span>
    </a>
    <a href="{{ route('family-100.tv') }}" class="btn btn-cyan" target="_blank" rel="noopener" title="Buka Layar TV (Universal)" aria-label="Buka Layar TV">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" /><path d="M16 3l-4 4l-4 -4" /></svg>
        <span class="btn-label">Buka TV ↗</span>
    </a>
    <button type="button" class="btn btn-primary" data-answer-form title="Tambah Jawaban" aria-label="Tambah Jawaban">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        <span class="btn-label">Tambah Jawaban</span>
    </button>
@endsection

@section('content')
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
                        TV Otomatis Terhubung
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

    <!-- Kartu Informasi Pertanyaan & Kontrol TV -->
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
                    <button type="button" class="btn btn-danger btn-lg px-3 px-md-4 py-3 shadow-sm w-100 mb-2" data-wrong-url="{{ route('family-100.questions.wrong', $question) }}" id="btn-strike">
                        <span class="fs-2 me-2 align-middle">❌</span>
                        <span class="fw-bold fs-3 align-middle">Tombol Salah (Strike)</span>
                    </button>
                    <div class="d-flex align-items-center justify-content-between bg-danger-subtle text-danger border border-danger-subtle rounded px-3 py-2 mb-2">
                        <span class="fw-semibold small">Jumlah Salah di Layar TV:</span>
                        <span class="badge bg-danger text-white fs-4 fw-bold px-3 py-1"><span data-wrong-count-badge>{{ $question->wrong_count }}</span>x</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2 shadow-sm" data-reset-round-url="{{ route('family-100.questions.reset', $question) }}" id="btn-reset-round" title="Tutup kembali semua jawaban dan kembalikan salah ke 0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" /></svg>
                        Reset Babak (Tutup Jawaban & Salah)
                    </button>
                </div>
            </div>
        </div>
    </div>

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
@endsection

@push('modals')
    <livewire:answer-form :question="$question" />
@endpush
