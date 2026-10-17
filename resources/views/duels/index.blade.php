@extends('layouts.app')

@section('title', 'Riwayat Duel')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">Riwayat duel</h1>
            <x-duel-stats :user="$user" class="mt-4" />
        </div>

        @if ($active->isNotEmpty())
            <x-card title="Sedang berjalan">
                <ul class="divide-y divide-navy-50">
                    @foreach ($active as $duel)
                        @php $rival = $duel->opponentOf($user); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$rival" size="sm" />
                                <span>
                                    <span class="block font-semibold">{{ $rival->name }}</span>
                                    <span class="block text-xs text-navy-500">Ronde {{ $duel->current_round }} · {{ $duel->status === 'battle' ? 'battle' : 'draft' }}</span>
                                </span>
                            </span>
                            <x-button :href="route('duels.show', $duel)" variant="secondary">Lanjutkan</x-button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <x-card class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-navy-900 text-xs uppercase tracking-wide text-navy-100">
                    <tr>
                        <th class="px-4 py-3">Lawan</th>
                        <th class="px-4 py-3">Hasil</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Skor ronde</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Tanggal</th>
                        <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-navy-50">
                    @forelse ($finished as $duel)
                        @php
                            $rival = $duel->opponentOf($user);
                            $result = match (true) {
                                $duel->winner_id === null => ['Seri', 'bg-navy-100 text-navy-800'],
                                (int) $duel->winner_id === $user->id => ['Menang', 'bg-ungu-100 text-ungu-700'],
                                default => ['Kalah', 'bg-red-50 text-red-700'],
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-3">
                                    <x-avatar :user="$rival" size="sm" class="max-sm:hidden" />
                                    <span class="font-semibold">{{ $rival->name }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $result[1] }}">{{ $result[0] }}</span>
                                <span class="ml-1 text-xs tabular-nums text-navy-600 sm:hidden">{{ $duel->rounds->where('winner_id', $user->id)->count() }}:{{ $duel->rounds->where('winner_id', $rival->id)->count() }}</span>
                            </td>
                            <td class="hidden px-4 py-3 tabular-nums sm:table-cell">
                                {{ $duel->rounds->where('winner_id', $user->id)->count() }} : {{ $duel->rounds->where('winner_id', $rival->id)->count() }}
                            </td>
                            <td class="hidden px-4 py-3 text-navy-600 sm:table-cell">{{ $duel->finished_at?->translatedFormat('j M Y, H.i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('duels.result', $duel) }}" class="inline-flex min-h-11 items-center font-semibold text-ungu-600 underline-offset-2 hover:underline">Lihat hasil</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-navy-500">Belum ada duel yang selesai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <div>{{ $finished->links() }}</div>
    </div>
@endsection
