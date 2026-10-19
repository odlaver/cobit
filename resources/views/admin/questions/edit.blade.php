@extends('layouts.app')

@section('title', 'Edit Soal')
@section('width', 'max-w-3xl')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.questions.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
            <x-icon name="back" /> Kembali ke Bank Soal
        </a>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Edit Pertanyaan #{{ $question->id }}</h1>
    </div>

    <x-card>
        <form method="POST" action="{{ route('admin.questions.update', $question) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="source" value="{{ $question->source ?? 'manual' }}">

            <div>
                <label for="material_id" class="field-label">Materi & Mata Kuliah</label>
                <select id="material_id" name="material_id" required
                    class="field py-2.5">
                    @foreach ($materials as $m)
                        <option value="{{ $m->id }}" @selected(old('material_id', $question->material_id) == $m->id)>
                            [{{ $m->course?->code }}] {{ $m->course?->name }} / {{ $m->title }}
                        </option>
                    @endforeach
                </select>
                @error('material_id')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="question_text" class="field-label">Teks Soal / Pertanyaan</label>
                <textarea id="question_text" name="question_text" rows="4" required
                    class="field py-2.5">{{ old('question_text', $question->question_text) }}</textarea>
                @error('question_text')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="mb-3 text-base font-bold text-navy-900">Pilihan jawaban</h3>
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 border-t border-slate-100 pt-4">
                <div>
                    <label for="correct_answer" class="field-label">Kunci Jawaban Benar</label>
                    <select id="correct_answer" name="correct_answer" required
                        class="field py-2.5">
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
                    <label for="difficulty" class="field-label">Tingkat kesulitan</label>
                    <select id="difficulty" name="difficulty" required class="field py-2.5">
                        @foreach (\App\Models\Question::DIFFICULTIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('difficulty', $question->difficulty) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('difficulty')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="field-label">Status Soal</label>
                    <select id="status" name="status" required
                        class="field py-2.5">
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
