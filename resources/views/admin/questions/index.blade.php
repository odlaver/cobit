@extends('layouts.app')

@section('title', 'Bank Soal')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Bank Soal</h1>
            <p class="mt-1 text-sm text-navy-600">Kelola pertanyaan, pilihan ganda, kunci jawaban, dan status tayang</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button :href="route('admin.questions.create')" variant="primary"><x-icon name="plus" /> Tambah Soal</x-button>
        </div>
    </div>

    <!-- Filter & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:min-w-60 sm:flex-1">
                <label for="search" class="field-label">Pencarian Teks Soal</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Ketik kata kunci soal..."
                    class="field">
            </div>

            <div class="w-full sm:w-44">
                <label for="status" class="field-label">Status</label>
                <select id="status" name="status"
                    class="field">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                </select>
            </div>

            <div class="w-full sm:w-36">
                <label for="difficulty" class="field-label">Kesulitan</label>
                <select id="difficulty" name="difficulty" class="field">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Question::DIFFICULTIES as $value => $label)
                        <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-36">
                <label for="source" class="field-label">Sumber</label>
                <select id="source" name="source" class="field">
                    <option value="">Semua</option>
                    <option value="manual" @selected(request('source') === 'manual')>Manual</option>
                    <option value="ai" @selected(request('source') === 'ai')>AI</option>
                </select>
            </div>

            <div class="w-full sm:w-52">
                <label for="course_id" class="field-label">Mata Kuliah</label>
                <select id="course_id" name="course_id"
                    class="field">
                    <option value="">Semua Mata Kuliah</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(request('course_id') == $c->id)>{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-52">
                <label for="material_id" class="field-label">Materi</label>
                <select id="material_id" name="material_id"
                    class="field">
                    <option value="">Semua Materi</option>
                    @foreach ($materials as $m)
                        <option value="{{ $m->id }}" @selected(request('material_id') == $m->id)>{{ $m->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary">Filter</x-button>
                @if (request()->hasAny(['search', 'status', 'difficulty', 'source', 'course_id', 'material_id']))
                    <x-button :href="route('admin.questions.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100 max-sm:hidden">
                <tr>
                    <th class="px-4 py-3">Pertanyaan & Pilihan</th>
                    <th class="hidden px-4 py-3 w-48 md:table-cell">Mata Kuliah & Materi</th>
                    <th class="hidden px-4 py-3 text-center w-24 md:table-cell">Sumber</th>
                    <th class="hidden px-4 py-3 text-center w-28 sm:table-cell">Status</th>
                    <th class="px-4 py-3 text-right w-44">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($questions as $question)
                    <tr class="align-top hover:bg-slate-50 max-sm:block max-sm:py-2">
                        <td class="px-4 py-3 max-sm:block max-sm:px-0 max-sm:pb-2">
                            <p class="font-medium text-navy-900 leading-snug">{{ $question->question_text }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <x-difficulty :level="$question->difficulty" />
                                @if ($question->source === 'ai')
                                    <x-badge tone="info" class="md:hidden">AI</x-badge>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-navy-600 md:hidden">
                                {{ $question->material?->course?->code }} · {{ $question->material?->title }}
                                <span class="sm:hidden">· {{ ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'draft' => 'Draft'][$question->status] ?? $question->status }}</span>
                            </p>
                            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                                <span class="rounded px-2 py-1 {{ $question->correct_answer === 'a' ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'bg-slate-100 text-slate-700' }}">
                                    A. {{ $question->option_a }}
                                </span>
                                <span class="rounded px-2 py-1 {{ $question->correct_answer === 'b' ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'bg-slate-100 text-slate-700' }}">
                                    B. {{ $question->option_b }}
                                </span>
                                <span class="rounded px-2 py-1 {{ $question->correct_answer === 'c' ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'bg-slate-100 text-slate-700' }}">
                                    C. {{ $question->option_c }}
                                </span>
                                <span class="rounded px-2 py-1 {{ $question->correct_answer === 'd' ? 'bg-emerald-100 text-emerald-800 font-bold border border-emerald-300' : 'bg-slate-100 text-slate-700' }}">
                                    D. {{ $question->option_d }}
                                </span>
                            </div>
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <span class="block text-xs font-semibold text-navy-700">
                                {{ $question->material?->course?->code }} - {{ $question->material?->course?->name }}
                            </span>
                            <span class="block text-xs text-slate-600 mt-0.5">
                                {{ $question->material?->title }}
                            </span>
                        </td>
                        <td class="hidden px-4 py-3 text-center md:table-cell">
                            @if ($question->source === 'ai')
                                <x-badge tone="info">AI</x-badge>
                            @else
                                <x-badge>Manual</x-badge>
                            @endif
                        </td>
                        <td class="hidden px-4 py-3 text-center sm:table-cell">
                            <x-question-status :status="$question->status" />
                        </td>
                        <td class="px-4 py-3 text-right max-sm:block max-sm:px-0 max-sm:pt-0">
                            <div class="flex flex-col items-end gap-1.5 max-sm:flex-row max-sm:flex-wrap max-sm:items-center">
                                <div class="inline-flex items-center gap-1.5">
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
                                </div>
                                <div class="inline-flex items-center gap-1.5">
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-navy-500">Tidak ada pertanyaan yang sesuai dengan kriteria filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">
        {{ $questions->links() }}
    </div>
@endsection
