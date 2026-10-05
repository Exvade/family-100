@extends('layouts.app')

@section('title', 'Edit Jawaban')
@section('page-pretitle', $question->question)
@section('page-title', 'Edit Jawaban')

@section('content')
    <form method="POST" action="{{ route('family-100.answers.update', $answer) }}" class="card">
        @method('PUT')
        <div class="card-body">
            @include('family-100.answers._form')
        </div>
    </form>
@endsection
