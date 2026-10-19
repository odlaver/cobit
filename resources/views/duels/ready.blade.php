@extends('layouts.app')

@section('title', 'Siap-siap Ronde ' . $round->round_number)
@section('width', 'max-w-3xl')
@section('game', 'true')

@section('content')
    @php
        $previous = match (true) {
            ! $lastRound => null,
            $lastRound->winner_id === null => 'Ronde ' . $lastRound->round_number . ' berakhir seri.',
            (int) $lastRound->winner_id === (int) auth()->id() => 'Kamu menang ronde ' . $lastRound->round_number . '.',
            default => $opponent->name . ' menang ronde ' . $lastRound->round_number . '.',
        };
        $seconds = (int) ceil($startsIn);
    @endphp

    <div class="mx-auto max-w-3xl" data-controller="ready"
        data-ready-seconds-value="{{ $startsIn }}"
        data-ready-url-value="{{ route('duels.battle', $duel) }}"
        data-ready-announce-value="{{ trim(($previous ?? '') . ' Ronde ' . $round->round_number . ' dimulai dalam ' . $seconds . ' detik.') }}">

        @include('duels.partials.header')

        <x-card class="text-center">
            @if ($previous)
                <p class="font-semibold text-navy-800">{{ $previous }}</p>
            @endif
            <p class="mt-1 text-sm text-navy-600">{{ $total }} soal · {{ \App\Services\BattleService::SECONDS }} detik per soal</p>

            <p class="mt-8 text-sm font-semibold text-navy-600">Mulai dalam</p>
            <p class="mt-1 text-7xl leading-none font-bold tabular-nums text-ungu-600 sm:text-8xl" data-ready-target="count" aria-hidden="true">{{ max(1, $seconds) }}</p>

            <noscript>
                <x-button :href="route('duels.battle', $duel)" class="mt-6">Mulai sekarang</x-button>
            </noscript>
        </x-card>
    </div>
@endsection
