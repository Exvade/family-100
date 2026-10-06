@props(['deleteUrl', 'deleteMessage' => 'Hapus data ini?'])

<span class="dropdown">
    <button class="btn dropdown-toggle align-text-top"
            data-bs-toggle="dropdown"
            data-bs-boundary="viewport">Opsi</button>
    <div class="dropdown-menu dropdown-menu-end shadow">
        {{ $slot }}
        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm(@js($deleteMessage))">
            @csrf
            @method('DELETE')
            <button type="submit" class="dropdown-item text-danger">Hapus</button>
        </form>
    </div>
</span>

