@extends('layouts.app')

@section('title', 'Edit Pertanyaan')
@section('page-pretitle', 'Family 100')
@section('page-title', 'Edit Pertanyaan')

@section('content')
    <form method="POST" action="{{ route('family-100.questions.update', $question) }}" class="card">
        @method('PUT')
        <div class="card-body">
            @include('family-100.questions._form')
        </div>
    </form>
@endsection
