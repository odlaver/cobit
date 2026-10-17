@extends('layouts.app')

@section('title', 'Kelola Materi Pembelajaran')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Materi Pembelajaran</h1>
            <p class="text-sm text-navy-500">Kelola topik dan materi di bawah setiap mata kuliah</p>
        </div>
        <div>
            <x-button :href="route('admin.materials.create')" variant="primary">+ Tambah Materi</x-button>
        </div>
    </div>

    <!-- Filter & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.materials.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="min-w-64 flex-1">
                <label for="search" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Pencarian Judul</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Cari judul materi..."
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
            </div>

            <div class="w-60">
                <label for="course_id" class="mb-1 block text-xs font-semibold uppercase text-navy-600">Mata Kuliah</label>
                <select id="course_id" name="course_id"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-ungu-500 focus:outline-none focus:ring-2 focus:ring-ungu-100">
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
                    <th class="px-4 py-3">Mata Kuliah</th>
                    <th class="px-4 py-3 text-center">Semester</th>
                    <th class="px-4 py-3 text-center">Jumlah Soal</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($materials as $material)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-navy-900">{{ $material->title }}</span>
                            @if ($material->description)
                                <p class="text-xs text-slate-500 line-clamp-1">{{ $material->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-navy-600">{{ $material->course?->code }}</span>
                            <span class="text-navy-800"> {{ $material->course?->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-navy-700">Sem {{ $material->course?->semester ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.questions.index', ['material_id' => $material->id]) }}" class="font-bold text-ungu-600 hover:underline">
                                {{ $material->questions_count }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.materials.edit', $material) }}" class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-navy-700 hover:bg-slate-100">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.materials.destroy', $material) }}" onsubmit="return confirm('Yakin ingin menghapus materi {{ $material->title }}? Semua soal di bawah materi ini akan ikut terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </form>
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
