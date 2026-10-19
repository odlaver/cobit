@extends('layouts.app')

@section('title', 'Tantangan')
@push('head')
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">
@endpush

@php
    $badges = [
        'pending' => ['Menunggu jawaban', 'warning'],
        'accepted' => ['Diterima', 'brand'],
        'declined' => ['Ditolak', 'danger'],
        'cancelled' => ['Dibatalkan', 'neutral'],
        'expired' => ['Kedaluwarsa', 'neutral'],
    ];
@endphp

@section('content')
    <div data-controller="presence">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Tantangan</h1>
                <p class="mt-1 text-sm text-navy-600">Tantang lawan, terima atau tolak tantangan yang masuk.</p>
            </div>
            <x-button :href="route('challenges.opponents')">Cari lawan</x-button>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 [&>*]:min-w-0">
            <x-card title="Tantangan masuk">
                @if ($inboxPending->isEmpty())
                    <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                        Belum ada tantangan masuk.
                    </p>
                @else
                    <ul class="divide-y divide-navy-50">
                        @foreach ($inboxPending as $challenge)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-avatar :user="$challenge->challenger" size="sm" :presence="$challenge->challenger->presence(in_array($challenge->challenger_id, $busy, true))" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">{{ $challenge->challenger->name }}</span>
                                        <span class="block text-xs break-words text-navy-600">{{ $challenge->roomLabel($roomCourses) }}</span>
                                        <span class="block text-xs text-navy-500">{{ $challenge->created_at->diffForHumans() }}</span>
                                    </span>
                                </span>
                                <span class="flex gap-2">
                                    <form method="POST" action="{{ route('challenges.accept', $challenge) }}">
                                        @csrf
                                        <x-button type="submit">Terima</x-button>
                                    </form>
                                    <form method="POST" action="{{ route('challenges.decline', $challenge) }}">
                                        @csrf
                                        <x-button type="submit" variant="secondary">Tolak</x-button>
                                    </form>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Tantangan terkirim">
                @if ($sentPending->isEmpty())
                    <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                        Tidak ada tantangan yang menunggu jawaban.
                    </p>
                @else
                    <ul class="divide-y divide-navy-50">
                        @foreach ($sentPending as $challenge)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-avatar :user="$challenge->opponent" size="sm" :presence="$challenge->opponent->presence(in_array($challenge->opponent_id, $busy, true))" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">{{ $challenge->opponent->name }}</span>
                                        <span class="block text-xs break-words text-navy-600">{{ $challenge->roomLabel($roomCourses) }}</span>
                                        <span class="block text-xs text-navy-500">{{ $challenge->created_at->diffForHumans() }}</span>
                                    </span>
                                </span>
                                <form method="POST" action="{{ route('challenges.cancel', $challenge) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary">Batalkan</x-button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        @php $history = $inboxDone->merge($sentDone)->sortByDesc('id')->values(); @endphp

        <div class="mt-6">
            <x-card title="Riwayat tantangan">
                @if ($history->isEmpty())
                    <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                        Belum ada riwayat tantangan.
                    </p>
                @else
                    <ul class="divide-y divide-navy-50">
                        @foreach ($history as $challenge)
                            @php
                                $mine = (int) $challenge->challenger_id === (int) $user->id;
                                $other = $mine ? $challenge->opponent : $challenge->challenger;
                                [$label, $tone] = $badges[$challenge->status] ?? ['Tidak diketahui', 'neutral'];
                            @endphp
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <span class="flex min-w-0 items-center gap-3">
                                    <x-avatar :user="$other" size="sm" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">
                                            {{ $mine ? 'Lawan ' : '' }}{{ $other->name }}
                                        </span>
                                        <span class="block text-xs text-navy-500">
                                            {{ $mine ? 'Dikirim' : 'Diterima' }} {{ $challenge->created_at->diffForHumans() }} · {{ $challenge->roomLabel($roomCourses) }}
                                        </span>
                                    </span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <x-badge :tone="$tone">{{ $label }}</x-badge>
                                    @if ($challenge->status === 'accepted' && $challenge->duel)
                                        <a href="{{ route('duels.show', $challenge->duel) }}" class="act whitespace-nowrap">Lihat duel</a>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
