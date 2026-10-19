@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Dashboard Admin</h1>
            <p class="mt-1 text-sm text-navy-600">Ringkasan kurikulum, pool turnamen, dan manajemen bank soal</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button :href="route('admin.courses.create')" variant="primary"><x-icon name="plus" /> Tambah Mata Kuliah</x-button>
        </div>
    </div>

    @php
        $min = \App\Services\DraftService::MIN_QUESTIONS;
        $ready = $courses->filter(fn ($c) => $c->active_questions_count >= $min && $c->is_active)->count();
    @endphp

    <!-- Statistik -->
    <dl class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-xl bg-navy-900 px-5 py-4 text-white">
            <dt class="text-sm text-navy-200">Matkul siap pool</dt>
            <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ $ready }}<span class="text-lg font-semibold text-navy-300">/{{ $courses->count() }}</span></dd>
        </div>
        <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
            <dt class="text-sm text-navy-600">Total soal</dt>
            <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($questionCount) }}</dd>
        </div>
        <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
            <dt class="text-sm text-navy-600">Total duel</dt>
            <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($duelCount) }}</dd>
        </div>
        <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
            <dt class="text-sm text-navy-600">Pengguna</dt>
            <dd class="mt-1 text-3xl font-extrabold tabular-nums">{{ number_format($userCount) }}</dd>
        </div>
    </dl>

    <!-- Tabel Soal per Matkul -->
    <x-card title="Mata Kuliah & Distribusi Soal">
        <p class="-mt-2 mb-4 text-sm text-navy-600">
            Matkul masuk pool duel kalau aktif dan punya minimal {{ $min }} soal aktif. Buka nama matkul untuk kelola materi dan soalnya.
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                    <tr>
                        <th class="hidden md:table-cell px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Mata Kuliah</th>
                        <th class="hidden md:table-cell px-4 py-3 text-center">Semester</th>
                        <th class="hidden md:table-cell px-4 py-3 text-center">Materi</th>
                        <th class="hidden md:table-cell px-4 py-3 text-center">Soal Aktif</th>
                        <th class="hidden px-4 py-3 text-center sm:table-cell">Status Pool</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-navy-50">
                    @forelse ($courses as $course)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="hidden px-4 py-3 font-mono font-bold text-navy-700 md:table-cell">{{ $course->code }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.courses.show', $course) }}" class="font-bold text-navy-900 hover:text-ungu-600 hover:underline">
                                    {{ $course->name }}
                                </a>
                                @if (!$course->is_active)
                                    <x-badge class="ml-2">Nonaktif</x-badge>
                                @endif
                                <span @class(['mt-0.5 block text-xs font-semibold sm:hidden', 'text-emerald-700' => $course->active_questions_count >= $min && $course->is_active, 'text-amber-700' => ! ($course->active_questions_count >= $min && $course->is_active)])>
                                    {{ $course->active_questions_count >= $min && $course->is_active ? 'Siap pool' : 'Belum siap pool' }} · {{ $course->active_questions_count }} soal aktif
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-center text-navy-700 md:table-cell">Sem {{ $course->semester }}</td>
                            <td class="hidden px-4 py-3 text-center font-medium text-navy-700 md:table-cell">
                                <a href="{{ route('admin.courses.show', $course) }}" class="hover:underline hover:text-ungu-600">
                                    {{ $course->materials_count }} Materi
                                </a>
                            </td>
                            <td class="hidden px-4 py-3 text-center font-bold md:table-cell {{ $course->active_questions_count >= $min ? 'text-ungu-600' : 'text-amber-600' }}">
                                <a href="{{ route('admin.courses.show', $course) }}" class="hover:underline">
                                    {{ $course->active_questions_count }}<span class="font-normal text-navy-500">/{{ $course->questions_count }}</span>
                                </a>
                            </td>
                            <td class="hidden px-4 py-3 text-center sm:table-cell">
                                @if ($course->active_questions_count >= $min && $course->is_active)
                                    <x-badge tone="success">Siap pool</x-badge>
                                @else
                                    <x-badge :tone="$course->is_active ? 'warning' : 'neutral'">
                                        {{ $course->active_questions_count < $min ? 'Kurang ' . ($min - $course->active_questions_count) . ' soal aktif' : 'Nonaktif' }}
                                    </x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative inline-block text-left" data-controller="dropdown">
                                    <button type="button" popovertarget="dropdown-course-{{ $course->id }}" data-dropdown-target="button" aria-haspopup="menu" aria-expanded="false"
                                        class="act gap-1.5">
                                        <span>Pilihan</span>
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div id="dropdown-course-{{ $course->id }}" popover data-dropdown-target="menu"
                                        class="fixed inset-auto m-0 w-48 rounded-lg border border-slate-200 bg-white px-0 py-1.5 text-left shadow-lg ring-1 ring-black/5">
                                        <div class="px-3 py-1.5 border-b border-slate-100 text-xs font-bold text-navy-800">
                                            {{ $course->code }} &bull; Sem {{ $course->semester }}
                                        </div>
                                        <a href="{{ route('admin.courses.show', $course) }}"
                                            class="flex min-h-11 items-center gap-2 px-3 py-2 text-xs sm:min-h-0 font-semibold text-navy-800 hover:bg-slate-100">
                                            <svg class="h-4 w-4 text-ungu-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Buka Materi & Soal
                                        </a>
                                        <a href="{{ route('admin.materials.create', ['course_id' => $course->id]) }}"
                                            class="flex min-h-11 items-center gap-2 px-3 py-2 text-xs sm:min-h-0 font-semibold text-navy-800 hover:bg-slate-100">
                                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Tambah Materi
                                        </a>
                                        <a href="{{ route('admin.questions.create', ['course_id' => $course->id]) }}"
                                            class="flex min-h-11 items-center gap-2 px-3 py-2 text-xs sm:min-h-0 font-semibold text-navy-800 hover:bg-slate-100">
                                            <svg class="h-4 w-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Tambah Soal
                                        </a>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <a href="{{ route('admin.courses.edit', $course) }}"
                                            class="flex min-h-11 items-center gap-2 px-3 py-2 text-xs sm:min-h-0 font-medium text-slate-700 hover:bg-slate-100">
                                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit Matkul
                                        </a>
                                        @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                            onsubmit="return confirm(@js('Hapus matkul '.$course->name.' beserta materi dan soalnya?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex min-h-11 w-full items-center gap-2 px-3 py-2 text-left text-xs sm:min-h-0 font-medium text-red-600 hover:bg-red-50">
                                                <svg class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus Matkul
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-navy-500">Belum ada mata kuliah yang didaftarkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
