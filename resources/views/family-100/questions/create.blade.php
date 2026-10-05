@extends('layouts.app')

@section('title', 'Tambah Pertanyaan')
@section('page-pretitle', 'Family 100')
@section('page-title', 'Tambah Pertanyaan')

@section('content')
    <form method="POST" action="{{ route('family-100.questions.store') }}" class="card">
        <div class="card-body">
            @include('family-100.questions._form')
        </div>
    </form>
@endsection
