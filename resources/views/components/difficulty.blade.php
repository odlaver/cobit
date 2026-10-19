@props(['level'])

@php
    $tone = ['mudah' => 'success', 'sulit' => 'danger'][$level] ?? 'neutral';
@endphp

<x-badge :tone="$tone" {{ $attributes }}>{{ \App\Models\Question::DIFFICULTIES[$level] ?? 'Sedang' }}</x-badge>
