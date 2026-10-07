<div class="modal modal-blur fade" id="participant-import-modal" tabindex="-1" aria-labelledby="participant-import-title" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="save" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="participant-import-title">Upload Peserta dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <ol class="ps-3 mb-3 text-secondary">
                    <li><a href="{{ route('doorprize.template') }}">Unduh template Excel</a> lalu isi nama peserta di kolom A, satu nama per baris.</li>
                    <li>Unggah file yang sudah diisi (.xlsx atau .csv, maks. 2 MB).</li>
                </ol>
                <p class="text-secondary small">Nama yang sudah ada (tanpa membedakan huruf besar/kecil) dilewati, jadi aman mengunggah ulang.</p>

                <label class="form-label required" for="participant-file-input">File</label>
                <input type="file" id="participant-file-input" class="form-control @error('file') is-invalid @enderror" wire:model="file" accept=".xlsx,.csv">
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint" wire:loading wire:target="file">Mengunggah file...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="file,save">
                    <span wire:loading.remove wire:target="save">Upload &amp; Impor</span>
                    <span wire:loading wire:target="save">Mengimpor...</span>
                </button>
            </div>
        </form>
    </div>
</div>
