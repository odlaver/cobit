@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-navy-100 bg-white p-4 shadow-sm sm:p-6']) }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-bold text-navy-900">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
