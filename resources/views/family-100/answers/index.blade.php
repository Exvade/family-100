@extends('layouts.app')

@section('title', 'Jawaban Family 100')
@section('page-pretitle', $question->question)
@section('page-title', 'Jawaban')

@section('page-actions')
    <a href="{{ route('family-100.questions.index') }}" class="btn">Kembali</a>
    <a href="{{ route('family-100.questions.tv', $question) }}" class="btn" target="_blank" rel="noopener">Tampil di TV</a>
    <a href="{{ route('family-100.answers.create', $question) }}" class="btn btn-primary">Tambah Jawaban</a>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="text-secondary small text-uppercase fw-bold">Kontrol TV</div>
                <div class="h3 m-0">Tampilan Jawaban & Efek Salah</div>
            </div>
            <div>
                <button type="button" class="btn btn-danger btn-lg px-4" data-wrong-url="{{ route('family-100.questions.wrong', $question) }}">
                    ❌ Tombol Salah (Strike)
                </button>
            </div>
        </div>
    </div>

    <div class="alert alert-info" role="alert">
        Di TV hanya tampil <strong>{{ $question->display_limit }}</strong> jawaban teratas dari {{ $answers->count() }} jawaban.
        Ubah di <a href="{{ route('family-100.questions.edit', $question) }}">pengaturan pertanyaan</a>.
    </div>

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
                            <a class="dropdown-item" href="{{ route('family-100.answers.edit', $answer) }}">Edit</a>
                        </x-row-actions>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-datatable>
@endsection
