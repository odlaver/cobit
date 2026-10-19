@props(['user', 'size' => 'md', 'presence' => null])

@php
    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-24 w-24 text-3xl',
        default => 'h-10 w-10 text-sm',
    };
    $dotClass = match ($size) {
        'sm' => 'size-2.5',
        'lg' => 'size-5 ring-4',
        default => 'size-3',
    };
@endphp

@if ($presence)
    <span class="relative inline-flex shrink-0">
@endif

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="Avatar {{ $user->name }}" {{ $attributes->merge(['class' => "$sizeClass shrink-0 rounded-full object-cover"]) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => "$sizeClass inline-flex shrink-0 items-center justify-center rounded-full bg-ungu-500 font-bold text-white"]) }}>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
@endif

@if ($presence)
        {{-- Titik status, offline disembunyikan --}}
        <span data-presence-dot="{{ $user->id }}" data-state="{{ $presence }}"
            class="absolute right-0 bottom-0 {{ $dotClass }} rounded-full bg-emerald-600 ring-2 ring-white data-[state=dueling]:bg-amber-500 data-[state=offline]:hidden">
            <span class="sr-only" data-presence-text>{{ $presence === 'dueling' ? 'Sedang berduel' : 'Online' }}</span>
        </span>
    </span>
@endif
