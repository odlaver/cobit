@props(['name'])

@php
    $paths = [
        'plus' => 'M12 5v14M5 12h14',
        'back' => 'M14.5 6 8.5 12l6 6',
        'next' => 'M9.5 6l6 6-6 6',
        'check' => 'M5 12.5 9.5 17 19 7',
        'x' => 'M7 7l10 10M17 7 7 17',
        'dash' => 'M7 12h10',
        'logout' => 'M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4M15 16l4-4-4-4M19 12H9',
    ];
@endphp

<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'size-4 shrink-0']) }}>
    <path d="{{ $paths[$name] }}" />
</svg>
