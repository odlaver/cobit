@props(['points' => null, 'tier' => null])

@php
    $tier ??= \App\Models\User::tierFor((int) $points);
    $color = match ($tier) {
        'God' => 'bg-amber-100 text-amber-800 ring-amber-300',
        'Hacker' => 'bg-ungu-100 text-ungu-700 ring-ungu-200',
        'Pro' => 'bg-cyan-100 text-cyan-800 ring-cyan-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-300',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide ring-1 ring-inset $color"]) }}>{{ $tier }}</span>
