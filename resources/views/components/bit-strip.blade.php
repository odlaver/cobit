@props(['cells', 'label'])

@php
    $styles = [
        'done' => ['bg-ungu-500', ''],
        'current' => ['bg-cyan-400 ring-2 ring-cyan-600 ring-offset-1', ''],
        'pending' => ['bg-navy-100', ''],
        'correct' => ['bg-ungu-500 text-white', 'check'],
        'wrong' => ['border-2 border-red-500 bg-white text-red-600', 'x'],
        'skipped' => ['border-2 border-dashed border-navy-200 bg-white text-navy-500', 'dash'],
    ];
    $names = ['correct' => 'benar', 'wrong' => 'salah', 'skipped' => 'tidak dijawab', 'done' => 'terjawab', 'current' => 'sekarang', 'pending' => 'belum'];
    $tall = collect($cells)->contains(fn ($c) => in_array($c, ['correct', 'wrong', 'skipped'], true));
@endphp

<ol {{ $attributes->merge(['class' => 'flex gap-1']) }} aria-label="{{ $label }}">
    @foreach ($cells as $i => $cell)
        <li @class([
            'flex flex-1 items-center justify-center rounded-sm text-xs font-bold leading-none',
            $tall ? 'h-6' : 'h-2.5',
            $styles[$cell][0],
        ])>
            @if ($styles[$cell][1])
                <x-icon :name="$styles[$cell][1]" class="size-3.5" />
            @endif
            <span class="sr-only">Soal {{ $i + 1 }} {{ $names[$cell] }}</span>
        </li>
    @endforeach
</ol>
