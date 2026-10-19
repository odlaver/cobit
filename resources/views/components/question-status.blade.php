@props(['status'])

@php
    [$label, $tone] = [
        'active' => ['Aktif', 'success'],
        'inactive' => ['Nonaktif', 'neutral'],
        'draft' => ['Draft', 'warning'],
    ][$status] ?? [ucfirst($status), 'neutral'];
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-badge>
