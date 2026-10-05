@csrf
<div class="mb-3">
    <label class="form-label required" for="answer">Nama Jawaban</label>
    <input type="text" id="answer" name="answer" class="form-control @error('answer') is-invalid @enderror"
           value="{{ old('answer', $answer->answer ?? '') }}" required autofocus>
    @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label required" for="ranking">Ranking</label>
    <input type="number" id="ranking" name="ranking" class="form-control @error('ranking') is-invalid @enderror"
           value="{{ old('ranking', $answer->ranking ?? $nextRanking ?? 1) }}" min="1" required>
    @error('ranking')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="card-footer text-end px-0 pb-0">
    <a href="{{ route('family-100.answers.index', $question) }}" class="btn btn-link">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
