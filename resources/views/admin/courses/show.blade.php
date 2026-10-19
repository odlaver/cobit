@extends('layouts.app')

@section('title', '[' . $course->code . '] ' . $course->name)

@section('content')
    @php
        $min = \App\Services\DraftService::MIN_QUESTIONS;
        $active = $course->questions()->where('status', 'active')->count();
        $eligible = $active >= $min && $course->is_active;
    @endphp

    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-navy-600">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center hover:underline">Dashboard Admin</a>
                <x-icon name="next" class="size-3.5 text-navy-400" />
            @endif
            <a href="{{ route('admin.courses.index') }}" class="inline-flex min-h-11 items-center hover:underline">Mata Kuliah</a>
            <x-icon name="next" class="size-3.5 text-navy-400" />
            <span class="text-navy-800">{{ $course->code }}</span>
        </div>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h1 class="text-2xl font-bold text-navy-900">{{ $course->name }}</h1>
                    <span class="font-mono text-sm font-bold text-navy-600 bg-navy-100 px-2 py-0.5 rounded">{{ $course->code }}</span>
                    @if (!$course->is_active)
                        <x-badge>Nonaktif</x-badge>
                    @endif
                </div>
                <p class="mt-1 text-sm text-navy-600">
                    Semester {{ $course->semester }} &bull; Program Studi {{ $course->prodi ?? 'SI' }}
                    @if ($course->description)
                        &bull; <span class="text-slate-600">{{ $course->description }}</span>
                    @endif
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <x-button :href="route('admin.materials.create', ['course_id' => $course->id])" variant="secondary">
                    <x-icon name="plus" /> Tambah Materi
                </x-button>
                <x-button :href="route('admin.questions.create', ['course_id' => $course->id])" variant="primary">
                    <x-icon name="plus" /> Tambah Soal
                </x-button>
                @if ($aiReady)
                    <form method="POST" action="{{ route('admin.courses.generate', $course) }}">
                        @csrf
                        <x-button variant="secondary">Generate {{ count(\App\Jobs\GenerateCourseQuestions::MIX) }} soal AI</x-button>
                    </form>
                @endif
                <x-button :href="route('admin.courses.edit', $course)" variant="ghost">
                    Edit Matkul
                </x-button>
            </div>
        </div>

        <!-- Pool Status Bar -->
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-navy-100 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $active >= $min ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-navy-900">
                        Status Pool Tournament:
                        @if ($eligible)
                            <span class="text-emerald-700">Memenuhi Syarat (Siap Duel)</span>
                        @else
                            <span class="text-amber-700">Belum Siap Masuk Pool</span>
                        @endif
                    </h3>
                    <p class="text-xs text-navy-500">
                        Terdapat {{ $active }} soal aktif dari syarat minimal {{ $min }} soal aktif.
                        Total materi: {{ $course->materials->count() }} materi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Materi & Soal -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-navy-900">Daftar Materi & Soal</h2>
            <span class="text-xs font-semibold text-navy-500">{{ $course->materials->count() }} Materi Terdaftar</span>
        </div>

        @forelse ($course->materials as $material)
            <x-card>
                <!-- Header Materi -->
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-ungu-100 text-xs font-bold text-ungu-700">
                                {{ $loop->iteration }}
                            </span>
                            <h3 class="text-base font-bold text-navy-900">{{ $material->title }}</h3>
                            <x-badge>{{ $material->questions_count }} soal</x-badge>
                        </div>
                        @if ($material->description)
                            <p class="mt-1 text-xs text-slate-600 pl-8">{{ $material->description }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.questions.create', ['course_id' => $course->id, 'material_id' => $material->id]) }}"
                            class="act border-ungu-200 bg-ungu-50 text-ungu-700 hover:bg-ungu-100">
                            Tambah soal ke materi ini
                        </a>
                        <a href="{{ route('admin.materials.edit', $material) }}"
                            class="act">
                            Edit Materi
                        </a>
                        @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.materials.destroy', $material) }}"
                            onsubmit="return confirm(@js('Hapus materi '.$material->title.' beserta seluruh soalnya?'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="act act-danger">
                                Hapus
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Daftar Soal di bawah Materi Ini -->
                <div class="pt-4">
                    @if ($material->questions->isEmpty())
                        <div class="py-6 text-center text-sm text-slate-600">
                            <p>Belum ada pertanyaan pada materi ini.</p>
                            <a href="{{ route('admin.questions.create', ['course_id' => $course->id, 'material_id' => $material->id]) }}"
                                class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-ungu-600 hover:underline">
                                Buat soal pertama untuk materi ini
                            </a>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 space-y-4">
                            @foreach ($material->questions as $question)
                                <div class="pt-4 first:pt-0">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start gap-2">
                                                <span class="mt-0.5 shrink-0 text-xs font-bold text-navy-500 tabular-nums">#{{ $loop->iteration }}</span>
                                                <p class="text-sm font-semibold text-navy-900 leading-snug">{{ $question->question_text }}</p>
                                            </div>

                                            <!-- Opsi Jawaban -->
                                            <div class="mt-2.5 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2 sm:pl-6">
                                                <div class="rounded-md px-2.5 py-1.5 border {{ $question->correct_answer === 'a' ? 'border-emerald-400 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700' }}">
                                                    <span class="font-bold mr-1">A.</span> {{ $question->option_a }}
                                                    @if ($question->correct_answer === 'a') <span class="ml-1 inline-flex items-center gap-0.5 align-middle font-bold text-emerald-700"><x-icon name="check" class="size-3.5" /> Kunci</span> @endif
                                                </div>
                                                <div class="rounded-md px-2.5 py-1.5 border {{ $question->correct_answer === 'b' ? 'border-emerald-400 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700' }}">
                                                    <span class="font-bold mr-1">B.</span> {{ $question->option_b }}
                                                    @if ($question->correct_answer === 'b') <span class="ml-1 inline-flex items-center gap-0.5 align-middle font-bold text-emerald-700"><x-icon name="check" class="size-3.5" /> Kunci</span> @endif
                                                </div>
                                                <div class="rounded-md px-2.5 py-1.5 border {{ $question->correct_answer === 'c' ? 'border-emerald-400 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700' }}">
                                                    <span class="font-bold mr-1">C.</span> {{ $question->option_c }}
                                                    @if ($question->correct_answer === 'c') <span class="ml-1 inline-flex items-center gap-0.5 align-middle font-bold text-emerald-700"><x-icon name="check" class="size-3.5" /> Kunci</span> @endif
                                                </div>
                                                <div class="rounded-md px-2.5 py-1.5 border {{ $question->correct_answer === 'd' ? 'border-emerald-400 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200 bg-white text-slate-700' }}">
                                                    <span class="font-bold mr-1">D.</span> {{ $question->option_d }}
                                                    @if ($question->correct_answer === 'd') <span class="ml-1 inline-flex items-center gap-0.5 align-middle font-bold text-emerald-700"><x-icon name="check" class="size-3.5" /> Kunci</span> @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status & Aksi Soal -->
                                        <div class="flex flex-row-reverse items-center justify-between gap-2 sm:flex-col sm:items-end">
                                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                                <x-difficulty :level="$question->difficulty" />
                                                @if ($question->source === 'ai')
                                                    <x-badge tone="info">AI</x-badge>
                                                @endif
                                                <x-question-status :status="$question->status" />
                                            </div>

                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @if ($question->status === 'draft')
                                                    <form method="POST" action="{{ route('admin.questions.approve', $question) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="act act-ok">
                                                            Approve
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.questions.toggle-status', $question) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="act">
                                                            {{ $question->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                @endif

                                                <a href="{{ route('admin.questions.edit', $question) }}" class="act">
                                                    Edit
                                                </a>

                                                @if (auth()->user()->isAdmin())
                                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('Hapus soal ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="act act-danger">
                                                        Hapus
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>
        @empty
            <x-card class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-3 text-base font-bold text-navy-900">Belum Ada Materi Pembelajaran</h3>
                <p class="mt-1 text-sm text-slate-600 max-w-md mx-auto">
                    Mata kuliah ini belum memiliki materi. Tambahkan materi terlebih dahulu sebelum menambahkan butir soal.
                </p>
                <div class="mt-5">
                    <x-button :href="route('admin.materials.create', ['course_id' => $course->id])" variant="primary">
                        <x-icon name="plus" /> Tambah Materi Pertama
                    </x-button>
                </div>
            </x-card>
        @endforelse
    </div>
@endsection
