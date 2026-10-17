@extends('layouts.app')

@section('title', 'Tantangan')

@php
    $badges = [
        'pending' => ['Menunggu jawaban', 'bg-amber-100 text-amber-800 ring-amber-300'],
        'accepted' => ['Diterima', 'bg-ungu-100 text-ungu-700 ring-ungu-200'],
        'declined' => ['Ditolak', 'bg-red-50 text-red-700 ring-red-200'],
        'cancelled' => ['Dibatalkan', 'bg-slate-100 text-slate-600 ring-slate-300'],
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Tantangan</h1>
            <p class="text-sm text-navy-500">Tantang lawan, terima atau tolak tantangan yang masuk.</p>
        </div>
        <x-button :href="route('challenges.opponents')">Cari lawan</x-button>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Tantangan masuk">
            @if ($inboxPending->isEmpty())
                <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                    Belum ada tantangan masuk.
                </p>
            @else
                <ul class="divide-y divide-navy-50">
                    @foreach ($inboxPending as $challenge)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$challenge->challenger" size="sm" />
                                <span>
                                    <span class="block font-semibold">{{ $challenge->challenger->name }}</span>
                                    <span class="block text-xs text-navy-500">{{ $challenge->challenger->npm }} · {{ $challenge->created_at->diffForHumans() }}</span>
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
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$challenge->opponent" size="sm" />
                                <span>
                                    <span class="block font-semibold">{{ $challenge->opponent->name }}</span>
                                    <span class="block text-xs text-navy-500">{{ $challenge->opponent->npm }} · {{ $challenge->created_at->diffForHumans() }}</span>
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
                            $label = $badges[$challenge->status] ?? ['Tidak diketahui', 'bg-slate-100 text-slate-600 ring-slate-300'];
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$other" size="sm" />
                                <span>
                                    <span class="block font-semibold">
                                        {{ $mine ? 'Lawan ' : '' }}{{ $other->name }}
                                    </span>
                                    <span class="block text-xs text-navy-500">
                                        {{ $mine ? 'Dikirim' : 'Diterima' }} {{ $challenge->created_at->diffForHumans() }}
                                    </span>
                                </span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide ring-1 ring-inset {{ $label[1] }}">{{ $label[0] }}</span>
                                @if ($challenge->status === 'accepted' && $challenge->duel && Route::has('duels.index'))
                                    <a href="{{ route('duels.index') }}" class="text-sm font-semibold text-ungu-600 underline-offset-2 hover:underline">Lihat duel</a>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
