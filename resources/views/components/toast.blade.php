@props(['tone' => 'success'])

<div data-controller="toast" data-toast-delay-value="{{ $tone === 'error' ? 8000 : 5000 }}"
    {{ $attributes->class([
        'pointer-events-auto flex w-full items-start gap-3 rounded-xl border bg-white p-4 shadow-lg shadow-navy-900/10',
        'border-red-200' => $tone === 'error',
        'border-navy-100' => $tone !== 'error',
    ]) }}>
    <span aria-hidden="true" @class([
        'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full text-white',
        'bg-red-600' => $tone === 'error',
        'bg-ungu-500' => $tone !== 'error',
    ])>
        <x-icon :name="$tone === 'error' ? 'alert' : 'check'" class="size-3" />
    </span>
    <p class="min-w-0 flex-1 text-sm font-medium break-words text-navy-900">{{ $slot }}</p>
    <button type="button" data-action="toast#close" aria-label="Tutup notifikasi"
        class="-m-2 flex size-11 shrink-0 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-navy-50 hover:text-navy-800">
        <x-icon name="x" class="size-4" />
    </button>
</div>
