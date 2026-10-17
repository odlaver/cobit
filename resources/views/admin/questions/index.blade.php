@extends('layouts.app')

@section('title', 'Bank Soal')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Bank Soal</h1>
            <p class="text-sm text-navy-500">Kelola pertanyaan, pilihan ganda, kunci jawaban, dan status tayang</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button :href="route('admin.questions.create')" variant="primary">+ Tambah Soal</x-button>
        </div>
    </div>

    <!-- Filter & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-60 flex-1">
                <label for="search" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Pencarian Teks Soal</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Ketik kata kunci soal..."
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
            </div>

            <div class="w-44">
                <label for="status" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Status</label>
                <select id="status" name="status"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                </select>
            </div>

            <div class="w-52">
                <label for="course_id" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Mata Kuliah</label>
                <select id="course_id" name="course_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
                    <option value="">Semua Mata Kuliah</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(request('course_id') == $c->id)>{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-52">
                <label for="material_id" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Materi</label>
                <select id="material_id" name="material_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
                    <option value="">Semua Materi</option>
                    @foreach ($materials as $m)
                        <option value="{{ $m->id }}" @selected(request('material_id') == $m->id)>{{ $m->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary">Filter</x-button>
                @if (request()->hasAny(['search', 'status', 'course_id', 'material_id']))
                    <x-button :href="route('admin.questions.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                <tr>
                    <th class="px-4 py-3">Pertanyaan & Pilihan</th>
                    <th class="px-4 py-3 w-48">Mata Kuliah & Materi</th>
                    <th class="px-4 py-3 text-center w-24">Sumber</th>
                    <th class="px-4 py-3 text-center w-28">Status</th>
                    <th class="px-4 py-3 text-right w-44">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($questions as $question)
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium text-navy-900 leading-snug">{{ $question->question_text }}</p>
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
                        <td class="px-4 py-3">
                            <span class="block font-mono text-xs font-semibold text-navy-600">
                                {{ $question->material?->course?->code }} - {{ $question->material?->course?->name }}
                            </span>
                            <span class="block text-xs text-slate-600 mt-0.5">
                                {{ $question->material?->title }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($question->source === 'ai')
                                <span class="inline-flex rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-800">AI</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">Manual</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($question->status === 'active')
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Aktif</span>
                            @elseif ($question->status === 'inactive')
                                <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Nonaktif</span>
                            @elseif ($question->status === 'draft')
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-col items-end gap-1.5">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($question->status === 'draft')
                                        <form method="POST" action="{{ route('admin.questions.approve', $question) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                                                Approve
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.questions.toggle-status', $question) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded border border-slate-200 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                                {{ $question->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.questions.edit', $question) }}" class="rounded border border-slate-200 px-2 py-1 text-xs font-medium text-navy-700 hover:bg-slate-100">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('Hapus soal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-200 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
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
