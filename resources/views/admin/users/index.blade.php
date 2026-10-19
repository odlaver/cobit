@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@php
    $roleLabels = ['admin' => 'Admin', 'asdos' => 'Asdos', 'mahasiswa' => 'Mahasiswa'];
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-navy-900">Pengguna</h1>
        <p class="mt-1 text-sm text-navy-600">Jadikan mahasiswa sebagai asdos supaya bisa ikut mengelola matkul, materi, dan soal.</p>
    </div>

    <!-- Filter & Search -->
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:min-w-64 sm:flex-1">
                <label for="search" class="field-label">Pencarian</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Nama, email, atau NPM..." class="field">
            </div>

            <div class="w-full sm:w-48">
                <label for="role" class="field-label">Role</label>
                <select id="role" name="role" class="field">
                    <option value="">Semua role</option>
                    @foreach ($roleLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-40">
                <label for="status" class="field-label">Status</label>
                <select id="status" name="status" class="field">
                    <option value="">Semua status</option>
                    <option value="online" @selected(request('status') === 'online')>Online</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary">Filter</x-button>
                @if (request()->hasAny(['search', 'role', 'status']))
                    <x-button :href="route('admin.users.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table -->
    <x-card class="overflow-x-auto" data-controller="presence">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                <tr>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="hidden px-4 py-3 md:table-cell">Email</th>
                    <th class="hidden px-4 py-3 lg:table-cell">Status</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3 text-right">Ubah role</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-3">
                                <span class="max-sm:hidden"><x-avatar :user="$user" size="sm" :presence="$user->presence(in_array($user->id, $busy, true))" /></span>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-navy-900">{{ $user->name }}</span>
                                    <span class="block text-xs text-navy-600">{{ $user->npm ?? '-' }}<span class="md:hidden"> · {{ $user->email }}</span></span>
                                    <x-presence :user="$user" :state="$user->presence(in_array($user->id, $busy, true))" class="text-xs text-navy-600 lg:hidden" />
                                </span>
                            </span>
                        </td>
                        <td class="hidden px-4 py-3 text-navy-700 md:table-cell">{{ $user->email }}</td>
                        <td class="hidden px-4 py-3 text-navy-700 lg:table-cell">
                            <x-presence :user="$user" :state="$user->presence(in_array($user->id, $busy, true))" />
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :tone="$user->isAdmin() ? 'dark' : ($user->isAsdos() ? 'brand' : 'neutral')">{{ $roleLabels[$user->role] ?? $user->role }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($user->isAdmin())
                                <span class="text-xs text-navy-600">Tidak bisa diubah</span>
                            @else
                                <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="role" value="{{ $user->isAsdos() ? 'mahasiswa' : 'asdos' }}">
                                    <button type="submit" class="act">
                                        {{ $user->isAsdos() ? 'Jadikan mahasiswa' : 'Jadikan asdos' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-navy-500">Tidak ada pengguna yang cocok dengan pencarian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
