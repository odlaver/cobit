@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Leaderboard</h1>
            <p class="mt-1 text-sm text-navy-600">Noob 0–99 · Pro 100–299 · Hacker 300–599 · God 600+</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (array_reverse(array_keys(\App\Models\User::TIERS)) as $tier)
                <x-tier-badge :tier="$tier" />
            @endforeach
        </div>
    </div>

    <x-card class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                <tr>
                    <th class="w-12 px-3 py-3 sm:w-16 sm:px-4">#</th>
                    <th class="px-3 py-3 sm:px-4">Mahasiswa</th>
                    <th class="px-3 py-3 sm:px-4">Tier</th>
                    <th class="px-3 py-3 sm:px-4 text-right">Poin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse ($users as $user)
                    <tr @class(['bg-ungu-50' => $user->is(auth()->user())])>
                        <td class="px-3 py-3 sm:px-4 font-bold tabular-nums text-navy-600">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-3 sm:px-4">
                            <a href="{{ route('profile.user', $user) }}" class="flex items-center gap-3 hover:underline">
                                <x-avatar :user="$user" size="sm" />
                                <span>
                                    <span class="block font-semibold">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="ml-1 rounded bg-ungu-500 px-1.5 py-0.5 text-xs font-bold text-white">kamu</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-navy-500">{{ $user->npm }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-3 py-3 sm:px-4"><x-tier-badge :points="$user->points" /></td>
                        <td class="px-3 py-3 sm:px-4 text-right font-bold tabular-nums text-ungu-600">{{ $user->points }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-navy-500">Belum ada mahasiswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
