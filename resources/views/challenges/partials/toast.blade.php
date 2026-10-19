@php
    $other = in_array($kind, ['incoming', 'cancelled'], true) ? $challenge->challenger : $challenge->opponent;
    $message = match ($kind) {
        'incoming' => $other->name . ' menantangmu',
        'accepted' => $other->name . ' menerima tantanganmu. Duel dimulai.',
        'declined' => $other->name . ' menolak tantanganmu.',
        'expired' => 'Tantanganmu ke ' . $other->name . ' kedaluwarsa.',
        'cancelled' => $other->name . ' membatalkan tantangannya.',
        default => '',
    };
@endphp

<div data-controller="toast" data-toast-sticky-value="{{ $kind === 'incoming' ? 'true' : 'false' }}"
    class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-navy-100 bg-white p-4 shadow-lg shadow-navy-900/10">
    <x-avatar :user="$other" size="sm" :presence="$kind === 'incoming' ? $other->presence(in_array($other->id, $busy, true)) : null" />

    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold break-words text-navy-900">{{ $message }}</p>

        @if ($kind === 'incoming')
            <p class="mt-0.5 text-xs break-words text-navy-600">{{ $challenge->roomLabel($courses) }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('challenges.accept', $challenge) }}">
                    @csrf
                    <x-button type="submit">Terima</x-button>
                </form>
                <form method="POST" action="{{ route('challenges.decline', $challenge) }}">
                    @csrf
                    <x-button type="submit" variant="secondary">Tolak</x-button>
                </form>
            </div>
        @endif
    </div>

    <button type="button" data-action="toast#close" aria-label="Tutup notifikasi"
        class="-m-2 flex size-11 shrink-0 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-navy-50 hover:text-navy-800">
        <x-icon name="x" class="size-4" />
    </button>
</div>
