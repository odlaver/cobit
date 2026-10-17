@extends('layouts.app')

@section('title', 'Edit Soal')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center text-sm font-medium text-navy-600 hover:text-navy-900">
            &larr; Kembali ke Bank Soal
        </a>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Edit Pertanyaan #{{ $question->id }}</h1>
    </div>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.questions.update', $question) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="source" value="{{ $question->source ?? 'manual' }}">

            <div>
                <label for="material_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Materi & Mata Kuliah</label>
                <select id="material_id" name="material_id" required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                    @foreach ($materials as $m)
                        <option value="{{ $m->id }}" @selected(old('material_id', $question->material_id) == $m->id)>
                            [{{ $m->course?->code }}] {{ $m->course?->name }} &rsaquo; {{ $m->title }}
                        </option>
                    @endforeach
                </select>
                @error('material_id')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="question_text" class="mb-1.5 block text-sm font-semibold text-slate-700">Teks Soal / Pertanyaan</label>
                <textarea id="question_text" name="question_text" rows="4" required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">{{ old('question_text', $question->question_text) }}</textarea>
                @error('question_text')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-navy-800">Pilihan Jawaban (A, B, C, D)</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input name="option_a" label="Pilihan A" :value="old('option_a', $question->option_a)" required />
                    </div>
                    <div>
                        <x-input name="option_b" label="Pilihan B" :value="old('option_b', $question->option_b)" required />
                    </div>
                    <div>
                        <x-input name="option_c" label="Pilihan C" :value="old('option_c', $question->option_c)" required />
                    </div>
                    <div>
                        <x-input name="option_d" label="Pilihan D" :value="old('option_d', $question->option_d)" required />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4">
                <div>
                    <label for="correct_answer" class="mb-1.5 block text-sm font-semibold text-slate-700">Kunci Jawaban Benar</label>
                    <select id="correct_answer" name="correct_answer" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                        <option value="a" @selected(old('correct_answer', $question->correct_answer) === 'a')>Pilihan A</option>
                        <option value="b" @selected(old('correct_answer', $question->correct_answer) === 'b')>Pilihan B</option>
                        <option value="c" @selected(old('correct_answer', $question->correct_answer) === 'c')>Pilihan C</option>
                        <option value="d" @selected(old('correct_answer', $question->correct_answer) === 'd')>Pilihan D</option>
                    </select>
                    @error('correct_answer')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status Soal</label>
                    <select id="status" name="status" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                        <option value="active" @selected(old('status', $question->status) === 'active')>Aktif (Dapat keluar dalam duel)</option>
                        <option value="draft" @selected(old('status', $question->status) === 'draft')>Draft (Menunggu persetujuan)</option>
                        <option value="inactive" @selected(old('status', $question->status) === 'inactive')>Nonaktif</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <x-button :href="route('admin.questions.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" variant="primary">Perbarui Soal</x-button>
            </div>
        </form>
    </x-card>
@endsection
