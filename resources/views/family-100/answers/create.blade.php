@extends('layouts.app')

@section('title', 'Tambah Jawaban')
@section('page-pretitle', $question->question)
@section('page-title', 'Tambah Jawaban')

@section('content')
    <form method="POST" action="{{ route('family-100.answers.store', $question) }}" class="card">
        <div class="card-body">
            @include('family-100.answers._form')
        </div>
    </form>
@endsection
