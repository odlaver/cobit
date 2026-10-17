@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50 ' . match ($variant) {
        'secondary' => 'border border-navy-200 bg-white text-navy-800 hover:border-navy-300 hover:bg-navy-50 focus-visible:ring-navy-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500',
        'ghost' => 'text-navy-700 hover:bg-navy-50 focus-visible:ring-navy-500',
        default => 'bg-ungu-500 text-white shadow-sm shadow-ungu-700/20 hover:bg-ungu-600 focus-visible:ring-ungu-500',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
