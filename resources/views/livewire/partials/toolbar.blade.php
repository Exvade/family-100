{{-- Mobile: [tombol urutkan] [kolom cari]; pilihan urutan ada di offcanvas bawah. --}}
@props(['sort', 'sortOptions'])

<div class="m-bar">
    <button type="button" class="btn btn-icon m-filter" data-bs-toggle="offcanvas" data-bs-target="#m-sort" aria-controls="m-sort" aria-label="Urutkan">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6h8" /><path d="M16 6h4" /><path d="M14 4v4" /><path d="M4 12h2" /><path d="M10 12h10" /><path d="M8 10v4" /><path d="M4 18h10" /><path d="M18 18h2" /><path d="M16 16v4" /></svg>
        @if ($sort !== 'no:asc')
            <span class="m-filter-dot"></span>
        @endif
    </button>

    <div class="m-search">
        <input type="search" class="form-control" placeholder="Cari..." aria-label="Cari" wire:model.live.debounce.300ms="search">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
    </div>
</div>

{{-- wire:ignore: supaya render ulang daftar tidak menutup offcanvas yang sedang terbuka. --}}
<div wire:ignore>
    <div class="offcanvas offcanvas-bottom m-offcanvas" tabindex="-1" id="m-sort" aria-labelledby="m-sort-title">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="m-sort-title">Urutkan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <select class="form-select mb-3" aria-label="Urutkan" wire:model.live="sort">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="offcanvas">Selesai</button>
        </div>
    </div>
</div>
