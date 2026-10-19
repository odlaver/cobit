@props(['tone' => 'neutral'])

@php
    $color = match ($tone) {
        'brand' => 'bg-ungu-100 text-ungu-700 ring-ungu-200',
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 ring-amber-300',
        'danger' => 'bg-red-50 text-red-700 ring-red-200',
        'info' => 'bg-cyan-100 text-cyan-800 ring-cyan-200',
        'dark' => 'bg-navy-900 text-white ring-navy-900',
        default => 'bg-slate-100 text-slate-700 ring-slate-300',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset $color"]) }}><span class="min-w-0 truncate">{{ $slot }}</span></span>
