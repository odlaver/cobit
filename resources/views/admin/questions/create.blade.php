@extends('layouts.app')

@section('title', 'Tambah Soal Baru')

@section('content')
    <div class="mb-6">
        @if ($selectedCourseId)
            <a href="{{ route('admin.courses.show', $selectedCourseId) }}" class="inline-flex items-center text-sm font-medium text-navy-600 hover:text-navy-900">
                &larr; Kembali ke Detail Mata Kuliah
            </a>
        @else
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center text-sm font-medium text-navy-600 hover:text-navy-900">
                &larr; Kembali ke Mata Kuliah
            </a>
        @endif
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Tambah Pertanyaan Baru</h1>
    </div>

    @if ($selectedCourse)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-ungu-200 bg-ungu-50 p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ungu-600 text-white font-mono text-xs font-bold">
                    {{ $selectedCourse->code }}
                </span>
                <div>
                    <p class="text-xs font-bold uppercase text-ungu-700">Mata Kuliah Terpilih</p>
                    <p class="text-sm font-bold text-navy-900">{{ $selectedCourse->name }} (Semester {{ $selectedCourse->semester }})</p>
                </div>
            </div>
            <a href="{{ route('admin.materials.create', ['course_id' => $selectedCourse->id]) }}"
                class="rounded-lg border border-ungu-300 bg-white px-3 py-1.5 text-xs font-semibold text-ungu-700 hover:bg-ungu-50">
                + Tambah Materi Baru
            </a>
        </div>
    @endif

    <x-card class="max-w-3xl">
        @if ($selectedCourse && $materials->isEmpty())
            <div class="py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="mt-3 text-base font-bold text-navy-900">Mata Kuliah Belum Memiliki Materi</h3>
                <p class="mt-1 text-sm text-slate-500 max-w-md mx-auto">
                    Untuk menambahkan butir soal, mata kuliah <strong>{{ $selectedCourse->name }}</strong> harus memiliki minimal satu materi pembelajaran.
                </p>
                <div class="mt-5">
                    <x-button :href="route('admin.materials.create', ['course_id' => $selectedCourse->id])" variant="primary">
                        + Tambah Materi untuk {{ $selectedCourse->name }}
                    </x-button>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('admin.questions.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="source" value="manual">
                @if ($selectedCourseId)
                    <input type="hidden" name="redirect_course_id" value="{{ $selectedCourseId }}">
                @endif

                <!-- Pilihan Materi -->
                <div>
                    <label for="material_id" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Pilih Materi Pembelajaran <span class="text-red-500">*</span>
                    </label>

                    @if ($selectedCourse)
                        <p class="mb-2 text-xs text-slate-500">Pilih materi di dalam mata kuliah <strong>{{ $selectedCourse->name }}</strong> tempat soal ini akan ditugaskan:</p>
                        <select id="material_id" name="material_id" required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                            <option value="">-- Pilih Materi untuk {{ $selectedCourse->code }} --</option>
                            @foreach ($materials as $m)
                                <option value="{{ $m->id }}" @selected(old('material_id', $selectedMaterialId) == $m->id)>
                                    {{ $m->title }} ({{ $m->questions_count ?? $m->questions()->count() }} soal terdaftar)
                                </option>
                            @endforeach
                        </select>
                    @else
                        <select id="material_id" name="material_id" required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                            <option value="">-- Pilih Materi Pembelajaran --</option>
                            @foreach ($courses as $c)
                                @if ($c->materials->isNotEmpty())
                                    <optgroup label="[{{ $c->code }}] {{ $c->name }} (Sem {{ $c->semester }})">
                                        @foreach ($c->materials as $m)
                                            <option value="{{ $m->id }}" @selected(old('material_id', $selectedMaterialId) == $m->id)>
                                                &rsaquo; {{ $m->title }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    @endif

                    @error('material_id')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="question_text" class="mb-1.5 block text-sm font-semibold text-slate-700">Teks Soal / Pertanyaan <span class="text-red-500">*</span></label>
                    <textarea id="question_text" name="question_text" rows="4" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100"
                        placeholder="Tuliskan butir soal di sini...">{{ old('question_text') }}</textarea>
                    @error('question_text')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-navy-800">Pilihan Jawaban (A, B, C, D)</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input name="option_a" label="Pilihan A" :value="old('option_a')" required placeholder="Teks pilihan A" />
                        </div>
                        <div>
                            <x-input name="option_b" label="Pilihan B" :value="old('option_b')" required placeholder="Teks pilihan B" />
                        </div>
                        <div>
                            <x-input name="option_c" label="Pilihan C" :value="old('option_c')" required placeholder="Teks pilihan C" />
                        </div>
                        <div>
                            <x-input name="option_d" label="Pilihan D" :value="old('option_d')" required placeholder="Teks pilihan D" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4">
                    <div>
                        <label for="correct_answer" class="mb-1.5 block text-sm font-semibold text-slate-700">Kunci Jawaban Benar <span class="text-red-500">*</span></label>
                        <select id="correct_answer" name="correct_answer" required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                            <option value="">-- Pilih Jawaban Benar --</option>
                            <option value="a" @selected(old('correct_answer') === 'a')>Pilihan A</option>
                            <option value="b" @selected(old('correct_answer') === 'b')>Pilihan B</option>
                            <option value="c" @selected(old('correct_answer') === 'c')>Pilihan C</option>
                            <option value="d" @selected(old('correct_answer') === 'd')>Pilihan D</option>
                        </select>
                        @error('correct_answer')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status Soal <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-4 focus:ring-ungu-100">
                            <option value="active" @selected(old('status', 'active') === 'active')>Aktif (Dapat keluar dalam duel)</option>
                            <option value="draft" @selected(old('status') === 'draft')>Draft (Menunggu persetujuan)</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                        </select>
                        @error('status')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    @if ($selectedCourseId)
                        <x-button :href="route('admin.courses.show', $selectedCourseId)" variant="secondary">Batal</x-button>
                    @else
                        <x-button :href="route('admin.courses.index')" variant="secondary">Batal</x-button>
                    @endif
                    <x-button type="submit" variant="primary">Simpan Soal</x-button>
                </div>
            </form>
        @endif
    </x-card>
@endsection
