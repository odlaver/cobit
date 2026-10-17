@extends('layouts.app')

@section('title', 'Edit Mata Kuliah ' . $course->name)
@section('width', 'max-w-2xl')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.courses.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
            <x-icon name="back" /> Kembali ke Daftar Mata Kuliah
        </a>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Edit Mata Kuliah: {{ $course->name }}</h1>
    </div>

    <x-card>
        <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input name="code" label="Kode Mata Kuliah" :value="old('code', $course->code)" required />
                <div>
                    <label for="semester" class="field-label">Semester</label>
                    <select id="semester" name="semester" required
                        class="field py-2.5">
                        @foreach (range(1, 5) as $sem)
                            <option value="{{ $sem }}" @selected(old('semester', $course->semester) == $sem)>Semester {{ $sem }}</option>
                        @endforeach
                    </select>
                    @error('semester')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <x-input name="name" label="Nama Mata Kuliah" :value="old('name', $course->name)" required />

            <x-input name="prodi" label="Program Studi" :value="old('prodi', $course->prodi)" />

            <div>
                <label for="description" class="field-label">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3"
                    class="field py-2.5">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $course->is_active))
                    class="h-4 w-4 rounded border-slate-300 text-ungu-600 focus:ring-ungu-500">
                <label for="is_active" class="text-sm font-semibold text-navy-800">Status Aktif (dapat digunakan dalam pool duel)</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <x-button :href="route('admin.courses.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" variant="primary">Perbarui Mata Kuliah</x-button>
            </div>
        </form>
    </x-card>
@endsection
