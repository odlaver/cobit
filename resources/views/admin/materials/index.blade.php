@extends('layouts.app')

@section('title', 'Kelola Materi Pembelajaran')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Materi Pembelajaran</h1>
            <p class="mt-1 text-sm text-navy-600">Kelola topik dan materi di bawah setiap mata kuliah</p>
        </div>
        <div>
            <x-button :href="route('admin.materials.create')" variant="primary"><x-icon name="plus" /> Tambah Materi</x-button>
        </div>
    </div>

    <!-- Filter & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.materials.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:min-w-64 sm:flex-1">
                <label for="search" class="field-label">Pencarian Judul</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Cari judul materi..."
                    class="field">
            </div>

            <div class="w-full sm:w-60">
                <label for="course_id" class="field-label">Mata Kuliah</label>
                <select id="course_id" name="course_id"
                    class="field">
                    <option value="">Semua Mata Kuliah</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected(request('course_id') == $c->id)>{{ $c->code }} - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary">Filter</x-button>
                @if (request()->hasAny(['search', 'course_id']))
                    <x-button :href="route('admin.materials.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table -->
    <x-card class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                <tr>
                    <th class="px-4 py-3">Materi</th>
                    <th class="hidden px-4 py-3 sm:table-cell">Mata Kuliah</th>
                    <th class="hidden px-4 py-3 text-center md:table-cell">Semester</th>
                    <th class="px-4 py-3 text-center">Soal</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($materials as $material)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-navy-900">{{ $material->title }}</span>
                            @if ($material->description)
                                <p class="text-xs text-slate-600 line-clamp-1">{{ $material->description }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-navy-600 sm:hidden">{{ $material->course?->code }} · {{ $material->course?->name }}</p>
                        </td>
                        <td class="hidden px-4 py-3 sm:table-cell">
                            <span class="font-mono text-xs font-semibold text-navy-600">{{ $material->course?->code }}</span>
                            <span class="text-navy-800"> {{ $material->course?->name }}</span>
                        </td>
                        <td class="hidden px-4 py-3 text-center text-navy-700 md:table-cell">Sem {{ $material->course?->semester ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.questions.index', ['material_id' => $material->id]) }}" class="font-bold text-ungu-600 hover:underline">
                                {{ $material->questions_count }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.materials.edit', $material) }}" class="act">
                                    Edit
                                </a>
                                @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('admin.materials.destroy', $material) }}" onsubmit="return confirm(@js('Yakin ingin menghapus materi '.$material->title.'? Semua soal di bawah materi ini akan ikut terhapus.'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="act act-danger">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-navy-500">Tidak ada materi yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">
        {{ $materials->links() }}
    </div>
@endsection
