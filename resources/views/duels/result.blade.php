@extends('layouts.app')

@section('title', 'Hasil Duel')
@section('width', 'max-w-4xl')

@php
    $headline = [
        'win' => ['Kamu menang', 'bg-ungu-600'],
        'lose' => ['Kamu kalah', 'bg-navy-800'],
        'draw' => ['Seri', 'bg-navy-700'],
        'ongoing' => ['Duel masih berjalan', 'bg-navy-700'],
    ][$outcome];
    $seconds = fn ($ms) => number_format($ms / 1000, 1, ',', '.') . ' dtk';
    $signed = fn ($n) => $n > 0 ? '+' . $n : str_replace('-', '−', (string) $n);
@endphp

@section('content')
    <div class="mx-auto max-w-4xl space-y-6"
        @if ($outcome !== 'ongoing') data-controller="result" data-result-duel-value="{{ $duel->id }}" data-result-outcome-value="{{ $outcome }}" @endif>
        <section class="overflow-hidden rounded-xl text-white {{ $headline[1] }}">
            <div class="px-6 pt-6 text-center sm:px-8">
                <p class="text-sm text-white/80">
                    Duel #{{ $duel->id }}
                    @if ($duel->finished_at) · {{ $duel->finished_at->translatedFormat('j F Y, H.i') }} @endif
                    @if ($room) · {{ $room->roomLabel() }} @endif
                </p>
                <h1 class="mt-1 text-3xl font-bold sm:text-4xl">{{ $headline[0] }}</h1>
            </div>

            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 px-4 py-6 sm:gap-6 sm:px-8">
                @foreach ([[$user, 'Kamu'], [$opponent, null]] as [$player, $tag])
                    @if ($loop->last)
                        <p class="order-2 text-4xl font-bold tabular-nums sm:text-6xl" aria-label="Skor ronde {{ $roundWins[0] }} lawan {{ $roundWins[1] }}">
                            <span data-result-target="number" data-to="{{ $roundWins[0] }}">{{ $roundWins[0] }}</span><span class="px-2 text-white/50">:</span><span data-result-target="number" data-to="{{ $roundWins[1] }}">{{ $roundWins[1] }}</span>
                        </p>
                    @endif
                    <div @class(['flex min-w-0 flex-col items-center gap-2 text-center', 'order-1' => $loop->first, 'order-3' => $loop->last])>
                        <x-avatar :user="$player" class="ring-2 ring-white/40" />
                        <p class="max-w-full truncate text-sm font-semibold sm:text-base">{{ $tag ?? $player->name }}</p>
                        <x-tier-badge :points="$player->points" />
                    </div>
                @endforeach
            </div>

            @if ($outcome !== 'ongoing')
                <p class="bg-black/15 px-6 py-3 text-center text-sm">
                    @if ($delta !== null)
                        <strong class="text-base tabular-nums" data-result-target="number" data-to="{{ $delta }}" data-sign="true">{{ $signed($delta) }}</strong> poin dari duel ini.
                        Poinmu sekarang <strong class="tabular-nums">{{ $user->points }}</strong>.
                        @if ($tierChange)
                            <span class="whitespace-nowrap">{{ $tierChange['up'] ? 'Naik ke tier' : 'Turun ke tier' }} <strong>{{ $tierChange['to'] }}</strong>.</span>
                        @endif
                    @else
                        Poinmu sekarang <strong>{{ $user->points }}</strong>.
                        Menang +{{ \App\Services\ScoringService::WIN_POINTS }}, seri +{{ \App\Services\ScoringService::DRAW_POINTS }}, kalah −{{ \App\Services\ScoringService::LOSS_POINTS }}.
                    @endif
                </p>
            @endif
        </section>

        @if ($summary->isEmpty())
            <x-card class="text-center text-navy-500">Belum ada ronde yang selesai.</x-card>
        @endif

        @foreach ($summary as $row)
            @php
                $winner = $row['round']->winner_id;
                $verdict = match (true) {
                    $winner === null => 'Seri',
                    (int) $winner === $user->id => 'Kamu menang',
                    default => $opponent->name . ' menang',
                };
            @endphp

            <x-card>
                <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-lg font-bold">Ronde {{ $row['round']->round_number }}</h2>
                    <p @class(['text-sm font-semibold', 'text-ungu-600' => (int) $winner === $user->id, 'text-navy-600' => (int) $winner !== $user->id])>{{ $verdict }}</p>
                </div>

                @if ($row['picks']->isNotEmpty())
                    <p class="mb-4 text-sm text-navy-600">
                        @foreach ($row['picks'] as $pick)
                            <span class="font-semibold text-navy-900">{{ $pick->course->name }}</span>
                            <span>(pick {{ (int) $pick->user_id === $user->id ? 'kamu' : $pick->user->name }})</span>@if (! $loop->last) &amp; @endif
                        @endforeach
                    </p>
                @endif

                <div class="space-y-4">
                    @foreach ([['Kamu', $row['me'], $row['myCells']], [$opponent->name, $row['opponent'], $row['opponentCells']]] as [$name, $stats, $cells])
                        <div>
                            <div class="mb-1.5 flex flex-wrap justify-between gap-2 text-sm">
                                <span class="font-semibold">{{ $name }}</span>
                                <span class="tabular-nums text-navy-600">{{ $stats['correct'] }}/{{ $stats['total'] }} benar · {{ $seconds($stats['time_ms']) }}</span>
                            </div>
                            <x-bit-strip :cells="$cells" label="Jawaban {{ $name }} di ronde {{ $row['round']->round_number }}" />
                        </div>
                    @endforeach
                </div>

                @if ($row['mistakes'])
                    <details class="mt-5 rounded-lg border border-navy-100">
                        <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-semibold text-navy-800 hover:bg-navy-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-ungu-500">
                            Pembahasan {{ count($row['mistakes']) }} soal yang belum tepat
                        </summary>
                        <ol class="divide-y divide-navy-50 px-4 pb-2">
                            @foreach ($row['mistakes'] as $mistake)
                                <li class="py-3 text-sm">
                                    <p class="mb-2 font-medium whitespace-pre-line">{{ $mistake['question']->question_text }}</p>
                                    <p class="text-red-700">
                                        Jawabanmu:
                                        @if ($mistake['selected'])
                                            {{ strtoupper($mistake['selected']) }}. {{ $mistake['question']->{'option_' . $mistake['selected']} }}
                                        @else
                                            tidak dijawab
                                        @endif
                                    </p>
                                    <p class="text-ungu-700">
                                        Kunci: {{ strtoupper($mistake['question']->correct_answer) }}. {{ $mistake['question']->{'option_' . $mistake['question']->correct_answer} }}
                                    </p>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
            </x-card>
        @endforeach

        @if ($mastery)
            <x-card title="Penguasaan matkul">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-navy-100 text-navy-600">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">Matkul</th>
                                <th class="py-2 pr-4 text-right font-semibold">Kamu</th>
                                <th class="py-2 text-right font-semibold">{{ $opponent->name }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-navy-50">
                            @foreach ($mastery as $course => $row)
                                <tr>
                                    <td class="py-2 pr-4">{{ $course }}</td>
                                    <td @class(['py-2 pr-4 text-right tabular-nums', 'font-bold text-ungu-600' => $row['me'] > $row['opponent']])>{{ $row['me'] }}/{{ $row['total'] }}</td>
                                    <td @class(['py-2 text-right tabular-nums', 'font-bold text-ungu-600' => $row['opponent'] > $row['me']])>{{ $row['opponent'] }}/{{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif

        <div class="flex flex-wrap gap-3">
            @if ($outcome === 'ongoing')
                @if ($duel->status === \App\Models\Duel::STATUS_BATTLE || Route::has('duels.draft'))
                    <x-button :href="route('duels.show', $duel)">Lanjutkan duel</x-button>
                @endif
            @elseif ($canRematch)
                {{-- Tantang ulang dengan room yang sama --}}
                <form method="POST" action="{{ route('challenges.store') }}">
                    @csrf
                    <input type="hidden" name="opponent_id" value="{{ $opponent->id }}">
                    <input type="hidden" name="rounds" value="{{ $room?->roundCount() ?? \App\Models\Duel::DEFAULT_ROUNDS }}">
                    @if (! empty($room?->course_ids))
                        <input type="hidden" name="mode" value="pilih">
                        @foreach ($room->course_ids as $courseId)
                            <input type="hidden" name="course_ids[]" value="{{ $courseId }}">
                        @endforeach
                    @endif
                    <x-button>Tantang {{ $opponent->name }} lagi</x-button>
                </form>
                <x-button variant="secondary" :href="route('challenges.room', ['opponent' => $opponent->id, 'from' => $duel->id])">Ubah room</x-button>
            @endif
            <x-button variant="secondary" :href="route('duels.index')">Riwayat duel</x-button>
            <x-button variant="ghost" :href="route('dashboard')">Dashboard</x-button>
        </div>

        @if ($tierChange && $tierChange['up'] && $tierChange['popup'])
            @php
                $emblem = [
                    'God' => 'bg-amber-400 text-amber-950',
                    'Hacker' => 'bg-ungu-500 text-white',
                    'Pro' => 'bg-cyan-400 text-navy-950',
                ][$tierChange['to']] ?? 'bg-navy-900 text-white';
            @endphp
            {{-- Rayakan naik tier, sekali per duel --}}
            <dialog data-result-target="tier" data-tier="{{ $tierChange['to'] }}" aria-label="Naik tier ke {{ $tierChange['to'] }}"
                data-action="click->result#closeTierOutside"
                class="tier-dialog m-auto w-[calc(100%-2rem)] max-w-sm overflow-visible rounded-2xl bg-white p-0 text-navy-900 shadow-2xl shadow-navy-950/30 backdrop:bg-navy-950/70">
                <div class="px-6 pt-8 pb-6 text-center">
                    <p class="text-sm font-semibold text-ungu-600">Naik tier</p>
                    <div class="relative mx-auto mt-4 size-28">
                        <span data-result-target="burst" class="absolute inset-0" aria-hidden="true"></span>
                        <p class="tier-emblem flex size-28 items-center justify-center rounded-3xl text-2xl font-extrabold tracking-wide uppercase {{ $emblem }}">{{ $tierChange['to'] }}</p>
                    </div>
                    <p class="mt-5 text-sm text-navy-600">{{ $tierChange['from'] }} ke {{ $tierChange['to'] }} · {{ $user->points }} poin</p>

                    @if ($tierChange['next'])
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-navy-100">
                            <div data-result-target="tierBar" data-width="{{ $tierChange['progress'] }}" class="h-full w-0 rounded-full bg-ungu-500 transition-[width] duration-700 ease-out"></div>
                        </div>
                        <p class="mt-2 text-xs text-navy-600">{{ $tierChange['to_next'] }} poin lagi ke {{ $tierChange['next'] }}</p>
                    @else
                        <p class="mt-4 text-xs font-semibold text-amber-800">Tier tertinggi</p>
                    @endif

                    <x-button type="button" class="mt-6 w-full" data-action="result#closeTier">Lanjut</x-button>
                </div>
            </dialog>
        @endif
    </div>
@endsection
