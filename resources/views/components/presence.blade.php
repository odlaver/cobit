@props(['user', 'state' => null, 'basic' => false])

@php
    $state ??= $user->presence();
    $state = $basic && $state === \App\Models\User::PRESENCE_DUELING ? \App\Models\User::PRESENCE_ONLINE : $state;
    $text = match ($state) {
        \App\Models\User::PRESENCE_ONLINE => 'Online',
        \App\Models\User::PRESENCE_DUELING => 'Sedang berduel',
        default => $user->last_seen_at ? 'Aktif ' . $user->last_seen_at->diffForHumans() : 'Belum pernah aktif',
    };
@endphp

<span data-presence-label="{{ $user->id }}" data-state="{{ $state }}" @if ($basic) data-basic @endif
    {{ $attributes->merge(['class' => 'group/presence inline-flex min-w-0 items-center gap-1.5']) }}>
    <span aria-hidden="true" class="size-2 shrink-0 rounded-full bg-slate-400 group-data-[state=dueling]/presence:bg-amber-500 group-data-[state=online]/presence:bg-emerald-600"></span>
    <span class="truncate" data-presence-text>{{ $text }}</span>
</span>
