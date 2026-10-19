@extends('layouts.app')

@section('title', 'Draft Room Ronde ' . $current_round)
@section('width', 'max-w-5xl')
@section('game', 'true')
@push('head')
    <meta name="turbo-refresh-scroll" content="preserve">
@endpush

@php
    $stepLabels = collect(range(0, $steps - 1))->map(fn ($i) => $i < $bans ? 'Ban ' . ($i + 1) : 'Pick ' . ($i - $bans + 1))->all();
    $stepGrid = [4 => 'grid-cols-2 sm:grid-cols-4', 3 => 'grid-cols-3', 2 => 'grid-cols-2'][$steps] ?? 'grid-cols-1';
    $actionLabel = ['ban' => 'Ban matkul', 'pick' => 'Pick matkul'][$action] ?? 'Draft selesai';
    $limit = \App\Services\DraftService::SECONDS;
    $running = $seconds_left > 0;
    $roundLabel = $max_round === 1 ? 'Ronde tunggal' : 'Ronde ' . $current_round . ' dari ' . $max_round;

    $last = $history->last();
    $lastMine = $last && (int) $last['user_id'] === (int) $user->id;
    $who = $last ? ($lastMine ? 'kamu' : $last['user_name']) : '';
    $narration = match (true) {
        ! $last => '',
        $last['is_auto'] => $last['course_name'] . ($last['action'] === 'ban' ? ' di-ban' : ' dipilih') . ' otomatis untuk ' . $who . '.',
        default => ucfirst($who) . ($last['action'] === 'ban' ? ' mem-ban ' : ' memilih ') . $last['course_name'] . '.',
    };

    $previousText = match (true) {
        ! $previous_round => null,
        $previous_round['winner_id'] === null => 'Ronde ' . $previous_round['number'] . ' berakhir seri.',
        $previous_round['winner_id'] === (int) $user->id => 'Kamu menang ronde ' . $previous_round['number'] . '.',
        default => $opponent->name . ' menang ronde ' . $previous_round['number'] . '.',
    };
@endphp

@section('content')
    <div id="draft" class="mx-auto max-w-5xl space-y-6"
        data-controller="draft"
        data-draft-state-url-value="{{ route('duels.state', $duel) }}"
        data-draft-step-value="{{ $step }}"
        data-draft-turn-value="{{ $turn_user_id ?? 0 }}"
        data-draft-seconds-value="{{ $seconds_left }}"
        data-draft-limit-value="{{ $limit }}"
        data-draft-my-turn-value="{{ $is_my_turn ? 'true' : 'false' }}"
        data-draft-duel-value="{{ $duel->id }}"
        data-draft-action-value="{{ $action }}"
        data-draft-last-id-value="{{ $last_draft_id }}"
        data-draft-last-mine-value="{{ $lastMine ? 'true' : 'false' }}"
        data-draft-last-auto-value="{{ $last && $last['is_auto'] ? 'true' : 'false' }}"
        data-draft-narration-value="{{ $narration }}">

        {{-- Kepala ruang draft --}}
        <div class="rounded-xl bg-navy-900 px-5 py-4 text-white" data-controller="presence">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <x-avatar :user="$opponent" size="sm" class="ring-2 ring-white/30" :presence="$opponent->presence(true)" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">Melawan {{ $opponent->name }}</p>
                        <p class="text-xs text-navy-200">{{ $roundLabel }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-navy-200">Skor ronde</p>
                    <p class="text-2xl font-bold tabular-nums">{{ $round_wins[0] }}<span class="px-1 text-navy-300">:</span>{{ $round_wins[1] }}</p>
                </div>
            </div>

            <p data-presence-note="{{ $opponent->id }}" @if ($opponent->isOnline()) hidden @endif
                class="mt-3 rounded-lg bg-white/10 px-3 py-2 text-xs text-navy-100">
                Lawan sedang offline.
            </p>
        </div>

        @if ($previousText)
            <p class="rounded-xl border border-navy-100 bg-white px-5 py-3 text-sm font-semibold text-navy-800">{{ $previousText }}</p>
        @endif

        {{-- Status giliran --}}
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-wide {{ $is_my_turn ? 'text-ungu-600' : 'text-navy-500' }}">
                        {{ $is_my_turn ? 'Giliran kamu' : 'Giliran ' . ($turn_user_name ?? 'lawan') }}
                    </p>
                    <p class="mt-1 text-xl font-bold">{{ $actionLabel }}</p>
                </div>

                <div class="flex flex-col items-end gap-3">
                    @if ($running)
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-navy-500">Sisa waktu</p>
                            <p class="text-4xl font-bold tabular-nums {{ $is_my_turn ? 'text-cyan-600' : 'text-navy-800' }}">
                                <span data-draft-target="timerLabel">{{ $seconds_left }}</span><span class="text-lg text-navy-400"> dtk</span>
                            </p>
                        </div>
                        <div class="h-2.5 w-40 overflow-hidden rounded-full bg-navy-100">
                            <div data-draft-target="timerBar" class="h-full bg-cyan-500 transition-[width] duration-200 ease-linear" style="width: {{ $limit > 0 ? (int) round($seconds_left / $limit * 100) : 0 }}%"></div>
                        </div>
                    @else
                        <p class="text-sm font-semibold text-navy-500">Draft ronde ini selesai.</p>
                    @endif
                </div>
            </div>

            {{-- Penanda langkah draft ronde ini --}}
            <ol class="mt-5 grid {{ $stepGrid }} gap-2" aria-label="Langkah draft">
                @foreach ($stepLabels as $index => $label)
                    <li @class([
                        'min-w-0 rounded-lg px-2 py-2 text-center text-xs font-bold',
                        'bg-ungu-500 text-white' => $index === $step && $is_my_turn,
                        'bg-navy-900 text-white' => $index === $step && ! $is_my_turn,
                        'bg-ungu-100 text-ungu-700' => $index < $step,
                        'bg-navy-50 text-navy-600' => $index > $step,
                    ])>
                        {{ $label }}
                        @if ($index < $step)
                            <span class="mt-0.5 block text-xs font-medium opacity-90">selesai</span>
                        @elseif ($index === $step)
                            <span class="mt-0.5 block text-xs font-medium opacity-90">sekarang</span>
                        @endif
                    </li>
                @endforeach
            </ol>

        </x-card>

        {{-- Matkul terpilih per ronde --}}
        <x-card title="Matkul terpilih">
            @if ($picks->isEmpty())
                <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-5 text-center text-sm text-navy-600">
                    Belum ada matkul yang di-pick.
                </p>
            @else
                <div class="space-y-3">
                    @foreach ($picks->sortKeys() as $roundNumber => $roundPicks)
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="w-10 shrink-0 text-xs font-bold uppercase tracking-wide text-navy-500">R{{ $roundNumber }}</span>
                            @foreach ($roundPicks as $pick)
                                <x-badge tone="dark">
                                    {{ $pick['course_name'] }}
                                    <span class="text-navy-200">· {{ (int) $pick['user_id'] === (int) $user->id ? 'kamu' : $pick['user_name'] }}</span>
                                </x-badge>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-navy-500">Pick hangus di ronde berikutnya, ban tidak.</p>
            @endif
        </x-card>

        {{-- Pool matkul --}}
        <section>
            <h2 class="mb-3 text-lg font-bold">Pool matkul ({{ $pool->count() }} {{ $is_room ? 'dari room' : 'terundi' }})</h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pool as $course)
                    @php
                        $pickable = $is_my_turn && $course['available'];

                        [$note, $noteTone] = match (true) {
                            (bool) $course['picked_round'] => ['Dipakai ronde ' . $course['picked_round'] . ' · ' . ($course['picked_by'] ?? '-'), 'danger'],
                            ! empty($course['banned_rounds']) => ['Di-ban ronde ' . implode(', ', $course['banned_rounds']) . ($course['available'] ? ', boleh dipakai lagi' : ''), 'warning'],
                            $course['available'] => ['Siap dipilih', 'info'],
                            default => ['Tidak tersedia', 'neutral'],
                        };
                    @endphp

                    @if ($pickable)
                        <form method="POST" action="{{ route('duels.draft.store', $duel) }}" class="js-act" data-action="submit->draft#submit">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $course['id'] }}">
                            <button type="submit"
                                class="block h-full w-full rounded-xl border-2 border-navy-100 bg-white p-4 text-left transition hover:border-ungu-500 hover:bg-ungu-50 focus:outline-none focus-visible:border-ungu-500 focus-visible:ring-4 focus-visible:ring-ungu-100">
                                @include('duels.partials.course-card', ['course' => $course, 'note' => $note, 'noteTone' => $noteTone])
                                <span class="mt-3 inline-flex rounded-md bg-ungu-500 px-3 py-1 text-xs font-bold text-white">
                                    {{ $action === 'ban' ? 'Ban matkul' : 'Pick matkul' }}
                                </span>
                            </button>
                        </form>
                    @else
                        <div @class([
                            'rounded-xl border-2 p-4',
                            'border-navy-100 bg-white' => $course['available'],
                            'border-slate-200 bg-slate-50' => ! $course['available'],
                        ])>
                            @include('duels.partials.course-card', ['course' => $course, 'note' => $note, 'noteTone' => $noteTone])
                            <span class="mt-3 inline-flex rounded-md bg-navy-50 px-3 py-1 text-xs font-bold text-navy-600">
                                {{ $course['available'] ? 'Menunggu giliran' : 'Tidak tersedia' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- Riwayat langkah --}}
        <x-card title="Riwayat draft">
            @if ($history->isEmpty())
                <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-5 text-center text-sm text-navy-600">
                    Belum ada langkah.
                </p>
            @else
                <ol class="divide-y divide-navy-50">
                    @foreach ($history as $row)
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2.5">
                            <span class="flex min-w-0 items-center gap-3 text-sm">
                                <x-badge :tone="$row['action'] === 'ban' ? 'danger' : 'brand'" class="w-14 justify-center uppercase">{{ $row['action'] }}</x-badge>
                                <span class="min-w-0">
                                    <span class="font-semibold">{{ $row['course_name'] }}</span>
                                    <span class="text-navy-500">· {{ (int) $row['user_id'] === (int) $user->id ? 'kamu' : $row['user_name'] }}</span>
                                </span>
                            </span>
                            <span class="flex items-center gap-2 text-xs text-navy-500">
                                R{{ $row['round'] }} · langkah {{ $row['step'] + 1 }}
                                @if ($row['is_auto'])
                                    <x-badge tone="warning">{{ $row['auto_reason'] === 'forced' ? 'otomatis' : 'waktu habis' }}</x-badge>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>

@endsection
