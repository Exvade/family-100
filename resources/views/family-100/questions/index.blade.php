@extends('layouts.app')

@section('title', 'Pertanyaan Family 100')
@section('page-pretitle', 'Family 100')
@section('page-title', 'Pertanyaan')

@section('page-actions')
    <a href="{{ route('family-100.questions.create') }}" class="btn btn-primary">Tambah Pertanyaan</a>
@endsection

@section('content')
    <div class="d-none d-md-block">
        <x-datatable title="Daftar Pertanyaan">
            <thead>
                <tr>
                    <th class="w-1"><button class="table-sort" data-dt-sort="no" data-dt-type="number">Nomor</button></th>
                    <th><button class="table-sort" data-dt-sort="question">Pertanyaan</button></th>
                    <th><button class="table-sort" data-dt-sort="answers" data-dt-type="number">Jumlah Jawaban</button></th>
                    <th><button class="table-sort" data-dt-sort="limit" data-dt-type="number">Tampil di TV</button></th>
                    <th class="w-1">Opsi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($questions as $question)
                    <tr data-row data-no="{{ $loop->iteration }}" data-question="{{ $question->question }}" data-answers="{{ $question->answers_count }}" data-limit="{{ $question->display_limit }}">
                        <td><span class="text-secondary">{{ $loop->iteration }}</span></td>
                        <td>{{ $question->question }}</td>
                        <td>{{ $question->answers_count }}</td>
                        <td>{{ $question->display_limit }} jawaban</td>
                        <td class="text-end">
                            <x-row-actions :delete-url="route('family-100.questions.destroy', $question)" delete-message="Hapus pertanyaan ini beserta semua jawabannya?">
                                <a class="dropdown-item" href="{{ route('family-100.answers.index', $question) }}">Kelola Jawaban</a>
                                <a class="dropdown-item" href="{{ route('family-100.questions.tv', $question) }}" target="_blank" rel="noopener">Tampil di TV</a>
                                <a class="dropdown-item" href="{{ route('family-100.questions.edit', $question) }}">Edit</a>
                            </x-row-actions>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-datatable>
    </div>

    <div class="d-md-none">
        <livewire:question-list />
    </div>
@endsection
