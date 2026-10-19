@extends('layouts.app')

@section('title', 'Cari Lawan')
@push('head')
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">
@endpush

@section('content')
    <div class="space-y-6" data-controller="presence">
        <div>
            <a href="{{ route('challenges.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
                <x-icon name="back" /> Kembali ke Tantangan
            </a>
            <h1 class="mt-2 text-2xl font-bold">Cari lawan</h1>
            <p class="mt-1 text-sm text-navy-600">Pilih mahasiswa untuk kamu tantang duel.</p>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <form method="GET" action="{{ route('challenges.opponents') }}" class="flex min-w-0 flex-1 flex-wrap gap-3">
                @if ($onlineOnly)
                    <input type="hidden" name="online" value="1">
                @endif
                <div class="min-w-0 flex-1 basis-60">
                    <x-input name="q" label="Filter nama" placeholder="Cari nama lawan…" :value="$q" />
                </div>
                <div class="flex gap-2 self-end">
                    <x-button type="submit">Cari</x-button>
                    @if ($q !== '')
                        <x-button variant="ghost" :href="route('challenges.opponents', array_filter(['online' => $onlineOnly ? 1 : null]))">Reset</x-button>
                    @endif
                </div>
            </form>

            {{-- Filter status online --}}
            <nav aria-label="Filter status" class="flex rounded-lg border border-navy-100 bg-white text-sm font-semibold">
                @foreach ([false => 'Semua', true => 'Online (' . $onlineCount . ')'] as $value => $label)
                    <a href="{{ route('challenges.opponents', array_filter(['q' => $q ?: null, 'online' => $value ? 1 : null])) }}"
                        @if ($onlineOnly === (bool) $value) aria-current="page" @endif
                        @class([
                            'inline-flex min-h-11 items-center px-4 transition-colors first:rounded-l-lg last:rounded-r-lg',
                            'bg-navy-900 text-white' => $onlineOnly === (bool) $value,
                            'text-navy-700 hover:bg-navy-50' => $onlineOnly !== (bool) $value,
                        ])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <x-card>
            @if ($opponents->isEmpty())
                <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                    @if ($onlineOnly)
                        Belum ada mahasiswa lain yang online.
                    @else
                        Tidak ada mahasiswa yang cocok dengan pencarianmu.
                    @endif
                </p>
            @else
                <ul class="divide-y divide-navy-50">
                    @foreach ($opponents as $opponent)
                        @php
                            $isPending = in_array((int) $opponent->id, $pending, true);
                            $isBusy = in_array((int) $opponent->id, $busy, true);
                            $presence = $opponent->presence($isBusy);
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="flex min-w-0 items-center gap-3">
                                <x-avatar :user="$opponent" size="sm" :presence="$presence" />
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold">{{ $opponent->name }}</span>
                                    <span class="flex flex-wrap items-center gap-x-1.5 text-xs text-navy-600">
                                        <x-presence :user="$opponent" :state="$presence" basic />
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $opponent->npm }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $opponent->points }} poin</span>
                                    </span>
                                </span>
                            </span>

                            <span class="flex flex-wrap items-center gap-2">
                                <x-tier-badge :points="$opponent->points" />

                                @if (in_array((int) $opponent->id, $incoming, true))
                                    <x-button variant="secondary" :href="route('challenges.index')">Jawab tantangannya</x-button>
                                @elseif ($isPending)
                                    <span class="text-xs font-semibold text-navy-600">Tantangan terkirim</span>
                                @elseif ($isBusy)
                                    <span class="text-xs font-semibold text-navy-600">Sedang berduel</span>
                                @else
                                    <form method="POST" action="{{ route('challenges.store') }}">
                                        @csrf
                                        <input type="hidden" name="opponent_id" value="{{ $opponent->id }}">
                                        <x-button type="submit">Tantang</x-button>
                                    </form>
                                    <x-button variant="secondary" :href="route('challenges.room', ['opponent' => $opponent->id])">Atur room</x-button>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div>{{ $opponents->links() }}</div>
    </div>
@endsection
