{{-- wire:ignore.self: render ulang tidak boleh membuang class/atribut "show" yang dipasang Bootstrap. --}}
<div class="modal modal-blur fade" id="participant-modal" tabindex="-1" aria-labelledby="participant-modal-title" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" wire:submit="save" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="participant-modal-title">{{ $participantId ? 'Edit Peserta' : 'Tambah Peserta' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required" for="participant-name-input">Nama Peserta</label>
                    <input type="text" id="participant-name-input" class="form-control @error('name') is-invalid @enderror" wire:model="name" maxlength="255" autocomplete="off" placeholder="Contoh: Budi Santoso">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label required" for="participant-category-select">Kategori Peserta</label>
                    <select id="participant-category-select" class="form-select @error('category') is-invalid @enderror" wire:model="category">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ \App\Models\Participant::displayLabel($cat) }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan</button>
            </div>
        </form>
    </div>
</div>
