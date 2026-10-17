@extends('layouts.app')

@section('title', 'Edit Materi ' . $material->title)
@section('width', 'max-w-2xl')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.materials.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
            <x-icon name="back" /> Kembali ke Daftar Materi
        </a>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Edit Materi: {{ $material->title }}</h1>
    </div>

    <x-card>
        <form method="POST" action="{{ route('admin.materials.update', $material) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="course_id" class="field-label">Mata Kuliah</label>
                <select id="course_id" name="course_id" required
                    class="field py-2.5">
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(old('course_id', $material->course_id) == $c->id)>
                            {{ $c->code }} - {{ $c->name }} (Sem {{ $c->semester }})
                        </option>
                    @endforeach
                </select>
                @error('course_id')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-input name="title" label="Judul Materi" :value="old('title', $material->title)" required />

            <div>
                <label for="description" class="field-label">Deskripsi Materi (Opsional)</label>
                <textarea id="description" name="description" rows="3"
                    class="field py-2.5">{{ old('description', $material->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <x-button :href="route('admin.materials.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" variant="primary">Perbarui Materi</x-button>
            </div>
        </form>
    </x-card>
@endsection
