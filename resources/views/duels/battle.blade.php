@extends('layouts.app')

@section('title', 'Battle Ronde ' . $round->round_number)
@section('width', 'max-w-3xl')
@section('game', 'true')

@section('content')
    @php
        $position = $myProgress + 1;
        $cells = collect(range(1, $total))->map(fn ($n) => $n < $position ? 'done' : ($n === $position ? 'current' : 'pending'))->all();
        $question = $item->question;
    @endphp

    <div id="battle" class="mx-auto max-w-3xl"
        data-controller="battle"
        data-battle-remaining-value="{{ $remaining }}" data-battle-limit-value="{{ $limit }}"
        data-battle-state-url-value="{{ route('duels.battle.state', ['duel' => $duel, 'round' => $round->round_number]) }}">

        @include('duels.partials.header')

        <div class="mb-3 flex flex-wrap items-end justify-between gap-2 text-sm">
            <p class="font-semibold">Soal {{ $position }} dari {{ $total }}</p>
            <p class="text-navy-500" aria-live="polite">
                {{ $opponent->name }}: <span data-battle-target="opponentProgress" class="font-semibold text-navy-800">{{ $opponentProgress }}</span>/{{ $total }} terjawab
            </p>
        </div>
        <x-bit-strip :cells="$cells" label="Progres jawabanmu" class="mb-6" />

        <x-card class="p-0!">
            <div class="h-2 overflow-hidden rounded-t-xl bg-navy-100">
                <div data-battle-target="timerBar" class="h-full bg-cyan-500 transition-[width] duration-200 ease-linear" style="width: {{ $remaining / $limit * 100 }}%"></div>
            </div>

            <div class="p-6 sm:p-8">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <p class="text-sm font-medium text-ungu-600">{{ $question->material->course->name }} · {{ $question->material->title }}</p>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-navy-800">
                        <span data-battle-target="timerLabel">{{ (int) ceil($remaining) }}</span> dtk
                    </p>
                </div>

                <h2 class="mb-8 text-lg leading-relaxed font-semibold whitespace-pre-line sm:text-xl">{{ $question->question_text }}</h2>

                <form method="POST" action="{{ route('duels.battle.answer', $duel) }}" data-answer-form data-battle-target="form" data-action="submit->battle#submit">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $question->id }}">

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Models\Question::ANSWERS as $key)
                            <button type="submit" name="answer" value="{{ $key }}" data-key="{{ $key }}"
                                class="group flex min-h-14 items-start gap-3 rounded-lg border-2 border-navy-100 bg-white p-4 text-left transition hover:border-ungu-500 hover:bg-ungu-50 focus:outline-none focus-visible:border-ungu-500 focus-visible:ring-4 focus-visible:ring-ungu-100 aria-disabled:pointer-events-none aria-disabled:opacity-60">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-navy-900 text-sm font-bold text-white uppercase group-hover:bg-ungu-500">{{ $key }}</span>
                                <span class="pt-0.5 text-sm leading-relaxed sm:text-base">{{ $question->{'option_' . $key} }}</span>
                            </button>
                        @endforeach
                    </div>
                </form>

                <p class="mt-6 hidden text-xs text-navy-500 sm:block">Pintasan keyboard: A, B, C, D</p>
            </div>
        </x-card>
    </div>

@endsection
