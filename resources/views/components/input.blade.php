@props(['name', 'label', 'type' => 'text', 'value' => null])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-navy-800">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password' && $type !== 'file') value="{{ $value }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class([
            'block min-h-11 w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs transition placeholder:text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-ungu-50 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-ungu-700 hover:file:bg-ungu-100 focus:outline-none focus:ring-4',
            'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has($name),
            'border-slate-300 focus:border-ungu-500 focus:ring-ungu-100' => ! $errors->has($name),
        ]) }}>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
