<div class="modal modal-blur fade" id="participant-import-modal" tabindex="-1" aria-labelledby="participant-import-title" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="save" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="participant-import-title">Upload Peserta dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 px-3 small mb-3">
                    <div class="fw-bold mb-1">Format Bebas Typo (Tidak Perlu Ketik Kategori):</div>
                    Di file Excel, cukup isi kolom <strong>Nama Peserta</strong>. Pilih kategori di bawah ini agar semua nama otomatis masuk ke kategori tersebut tanpa resiko salah ketik.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" for="participant-import-category">Kategori Peserta yang Diupload</label>
                    <select id="participant-import-category" class="form-select @error('category') is-invalid @enderror" wire:model="category">
                        <option value="">Otomatis (Ikuti Nama Sheet di Excel atau Kolom B)</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">Khusus Kategori: {{ \App\Models\Participant::displayLabel($cat) }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">
                        Bila memilih kategori spesifik, semua nama peserta di file akan otomatis dimasukkan ke kategori tersebut.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label required fw-bold" for="participant-file-input">File Excel (.xlsx) / CSV</label>
                    <input type="file" id="participant-file-input" class="form-control @error('file') is-invalid @enderror" wire:model="file" accept=".xlsx,.csv">
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint" wire:loading wire:target="file">Mengunggah file...</div>
                </div>

                <div class="bg-body-tertiary p-3 rounded border small">
                    <div class="fw-semibold text-secondary mb-2">Unduh Template Excel:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('doorprize.template') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            Template Multi-Sheet (5 Kategori)
                        </a>
                        @foreach ($categories as $cat)
                            <a href="{{ route('doorprize.template', ['category' => $cat]) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                Template {{ \App\Models\Participant::displayLabel($cat) }}
                            </a>
                        @endforeach
                    </div>
                </div>
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
