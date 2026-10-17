@extends('layouts.app')

@section('title', 'Edit Mata Kuliah ' . $course->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center text-sm font-medium text-navy-600 hover:text-navy-900">
            &larr; Kembali ke Daftar Mata Kuliah
        </a>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Edit Mata Kuliah: {{ $course->name }}</h1>
    </div>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input name="code" label="Kode Mata Kuliah" :value="old('code', $course->code)" required />
                <div>
                    <label for="semester" class="mb-1.5 block text-sm font-semibold text-slate-700">Semester</label>
                    <select id="semester" name="semester" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
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
                <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" rows="3"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $course->is_active))
                    class="h-4 w-4 rounded border-slate-300 text-ungu-600 focus:ring-ungu-500">
                <label for="is_active" class="text-sm font-medium text-slate-700">Status Aktif (dapat digunakan dalam pool duel)</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <x-button :href="route('admin.courses.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" variant="primary">Perbarui Mata Kuliah</x-button>
            </div>
        </form>
    </x-card>
@endsection
