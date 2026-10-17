@extends('layouts.app')

@section('title', 'Cari Lawan')

@section('content')
    <div class="space-y-6">
        <div>
            <a href="{{ route('challenges.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
                <x-icon name="back" /> Kembali ke Tantangan
            </a>
            <h1 class="mt-2 text-2xl font-bold">Cari lawan</h1>
            <p class="mt-1 text-sm text-navy-600">Pilih mahasiswa untuk kamu tantang berduel.</p>
        </div>

        <form method="GET" action="{{ route('challenges.opponents') }}" class="flex flex-wrap gap-3">
            <div class="min-w-60 flex-1">
                <x-input name="q" label="Filter nama" placeholder="Cari nama lawan…" :value="$q" />
            </div>
            <div class="self-end">
                <x-button type="submit">Cari</x-button>
            </div>
            @if ($q !== '')
                <div class="self-end">
                    <x-button variant="ghost" :href="route('challenges.opponents')">Reset</x-button>
                </div>
            @endif
        </form>

        <x-card>
            @if ($opponents->isEmpty())
                <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-6 text-center text-sm text-navy-600">
                    Tidak ada mahasiswa yang cocok dengan pencarianmu.
                </p>
            @else
                <ul class="divide-y divide-navy-50">
                    @foreach ($opponents as $opponent)
                        @php
                            $isPending = in_array((int) $opponent->id, $pending, true);
                            $isBusy = in_array((int) $opponent->id, $busy, true);
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span class="flex items-center gap-3">
                                <x-avatar :user="$opponent" size="sm" />
                                <span>
                                    <span class="block font-semibold">{{ $opponent->name }}</span>
                                    <span class="block text-xs text-navy-500">
                                        {{ $opponent->npm }} · Angkatan {{ $opponent->angkatan ?? '-' }} · {{ $opponent->points }} poin
                                    </span>
                                </span>
                            </span>

                            <span class="flex items-center gap-3">
                                <x-tier-badge :points="$opponent->points" />

                                @if ($isPending)
                                    <span class="text-xs font-semibold text-navy-500">Tantangan terkirim</span>
                                @elseif ($isBusy)
                                    <span class="text-xs font-semibold text-navy-500">Sedang berduel</span>
                                @else
                                    <form method="POST" action="{{ route('challenges.store') }}">
                                        @csrf
                                        <input type="hidden" name="opponent_id" value="{{ $opponent->id }}">
                                        <x-button type="submit">Tantang</x-button>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div>{{ $opponents->links() }}</div>
    </div>

    @include('challenges.partials.watch')
@endsection
