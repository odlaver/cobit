@extends('layouts.app')

@section('title', 'Menunggu Lawan')
@section('width', 'max-w-3xl')
@section('game', 'true')
@push('head')
    <meta name="turbo-refresh-scroll" content="preserve">
@endpush

@section('content')
    <div id="waiting" class="mx-auto max-w-3xl"
        data-controller="waiting"
        data-waiting-state-url-value="{{ route('duels.battle.state', ['duel' => $duel, 'round' => $round->round_number]) }}">

        @include('duels.partials.header')

        <x-card class="text-center">
            <p class="text-sm font-semibold text-ungu-600">Semua {{ $total }} soal sudah kamu jawab</p>
            <h2 class="mt-2 text-xl font-bold">Menunggu {{ $opponent->name }} selesai</h2>

            <div class="mx-auto mt-6 max-w-sm">
                <x-bit-strip :cells="collect(range(1, $total))->map(fn ($n) => $n <= $opponentProgress ? 'done' : 'pending')->all()"
                    label="Progres {{ $opponent->name }}" data-waiting-target="strip" />
                <p class="mt-3 text-sm text-navy-500" aria-live="polite" data-waiting-target="status">
                    {{ $opponentProgress }} dari {{ $total }} soal terjawab
                </p>
            </div>

            <p class="mt-6 text-xs text-navy-500">
                Hasil ronde muncul otomatis.
                <a href="{{ route('duels.battle', $duel) }}" class="font-semibold text-ungu-600 underline-offset-2 hover:underline">Cek sekarang</a>
            </p>
        </x-card>
    </div>

@endsection
