@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Leaderboard</h1>
            <p class="text-sm text-navy-500">Noob 0–99 · Pro 100–299 · Hacker 300–599 · God 600+</p>
        </div>
        <div class="flex gap-2">
            @foreach (array_reverse(array_keys(\App\Models\User::TIERS)) as $tier)
                <x-tier-badge :tier="$tier" />
            @endforeach
        </div>
    </div>

    <x-card class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                <tr>
                    <th class="px-4 py-3 w-16">#</th>
                    <th class="px-4 py-3">Mahasiswa</th>
                    <th class="px-4 py-3">Tier</th>
                    <th class="px-4 py-3 text-right">Poin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($users as $user)
                    <tr @class(['bg-ungu-50' => $user->is(auth()->user())])>
                        <td class="px-4 py-3 font-bold text-navy-500">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('profile.user', $user) }}" class="flex items-center gap-3 hover:underline">
                                <x-avatar :user="$user" size="sm" />
                                <span>
                                    <span class="block font-semibold">{{ $user->name }}</span>
                                    <span class="block text-xs text-navy-500">{{ $user->npm }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3"><x-tier-badge :points="$user->points" /></td>
                        <td class="px-4 py-3 text-right font-bold text-ungu-600">{{ $user->points }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-navy-500">Belum ada mahasiswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
