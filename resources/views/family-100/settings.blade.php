@extends('layouts.app')

@section('title', 'Pengaturan Family 100')
@section('page-pretitle', 'Family 100')
@section('page-title', 'Pengaturan')

@section('content')
    <form method="POST" action="{{ route('family-100.settings.update') }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label required" for="timer_duration">Durasi timer (detik)</label>
                <input type="number" id="timer_duration" name="timer_duration" class="form-control @error('timer_duration') is-invalid @enderror"
                       value="{{ old('timer_duration', $timerDuration) }}" min="5" max="3600" required>
                @error('timer_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-hint">Waktu hitung mundur untuk satu pertanyaan. Berlaku untuk semua pertanyaan Family 100.</small>
            </div>
            <div class="card-footer text-end px-0 pb-0">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </form>
@endsection
