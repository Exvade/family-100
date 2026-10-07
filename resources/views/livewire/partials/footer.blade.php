{{-- Sentinel infinite scroll: saat terlihat, komponen memuat baris berikutnya. Key per jumlah termuat agar observer dipasang ulang. --}}
@props(['hasMore', 'shown', 'total', 'loaded'])

@if ($hasMore)
    <div class="m-more" wire:key="more-{{ $loaded }}" wire:intersect.margin.300px="loadMore">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Memuat...
    </div>
@elseif ($total > 0)
    <div class="m-more" wire:key="end">Menampilkan semua {{ $total }} data</div>
@endif
