@props(['user', 'size' => 'md'])

@php
    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-24 w-24 text-3xl',
        default => 'h-10 w-10 text-sm',
    };
@endphp

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="Avatar {{ $user->name }}" {{ $attributes->merge(['class' => "$sizeClass shrink-0 rounded-full object-cover"]) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => "$sizeClass inline-flex shrink-0 items-center justify-center rounded-full bg-ungu-500 font-bold text-white"]) }}>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
@endif
