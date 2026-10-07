{{-- wire:ignore.self: render ulang tidak boleh membuang class/atribut "show" yang dipasang Bootstrap. --}}
<div class="modal modal-blur fade" id="answer-modal" tabindex="-1" aria-labelledby="answer-modal-title" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="save" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="answer-modal-title">{{ $answerId ? 'Edit Jawaban' : 'Tambah Jawaban' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required" for="answer-input">Nama Jawaban</label>
                    <input type="text" id="answer-input" class="form-control @error('answer') is-invalid @enderror" wire:model="answer" maxlength="255" autocomplete="off">
                    @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label required" for="ranking-input">Ranking</label>
                    <input type="number" id="ranking-input" class="form-control @error('ranking') is-invalid @enderror" wire:model="ranking" min="1" inputmode="numeric">
                    @error('ranking')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan</button>
            </div>
        </form>
    </div>
</div>
