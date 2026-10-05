@csrf
<div class="mb-3">
    <label class="form-label required" for="question">Pertanyaan</label>
    <input type="text" id="question" name="question" class="form-control @error('question') is-invalid @enderror"
           value="{{ old('question', $question->question ?? '') }}" placeholder="Sebutkan benda yang ada di dapur" required autofocus>
    @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label required" for="display_limit">Jumlah jawaban yang tampil di TV</label>
    <input type="number" id="display_limit" name="display_limit" class="form-control @error('display_limit') is-invalid @enderror"
           value="{{ old('display_limit', $question->display_limit ?? 5) }}" min="1" max="20" required>
    @error('display_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-hint">Jawaban ditampilkan berurutan sesuai ranking, mulai dari ranking 1.</small>
</div>
<div class="card-footer text-end px-0 pb-0">
    <a href="{{ route('family-100.questions.index') }}" class="btn btn-link">Batal</a>
    <button type="submit" class="btn btn-primary">Simpan</button>
</div>
