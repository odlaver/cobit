@extends('layouts.app')

@section('title', 'Draft Room Ronde ' . $current_round)

@php
    $stepLabels = ['Ban 1', 'Ban 2', 'Pick 1', 'Pick 2'];
    $actionLabel = ['ban' => 'Ban matkul', 'pick' => 'Pick matkul'][$action] ?? 'Draft selesai';
    $limit = \App\Services\DraftService::SECONDS;
    $running = $seconds_left > 0;
@endphp

@section('content')
    <div id="draft" class="mx-auto max-w-5xl space-y-6"
        data-state-url="{{ route('duels.state', $duel) }}"
        data-step="{{ $step }}"
        data-turn="{{ $turn_user_id }}"
        data-seconds="{{ $seconds_left }}"
        data-limit="{{ $limit }}">

        {{-- Kepala ruang draft --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-navy-900 px-5 py-4 text-white">
            <div class="flex items-center gap-3">
                <x-avatar :user="$opponent" size="sm" class="ring-2 ring-white/30" />
                <div>
                    <p class="text-sm font-semibold">Melawan {{ $opponent->name }}</p>
                    <p class="text-xs text-navy-200">Ronde {{ $current_round }} dari {{ $max_round }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-navy-200">Skor ronde</p>
                <p class="text-2xl font-bold tabular-nums">{{ $round_wins[0] }}<span class="px-1 text-navy-300">:</span>{{ $round_wins[1] }}</p>
            </div>
        </div>

        {{-- Status giliran --}}
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide {{ $is_my_turn ? 'text-ungu-600' : 'text-navy-500' }}">
                        {{ $is_my_turn ? 'Giliran kamu' : 'Giliran '.($turn_user_name ?? 'lawan') }}
                    </p>
                    <p class="mt-1 text-xl font-bold">{{ $actionLabel }}</p>
                    <p class="mt-1 text-sm text-navy-500">
                        {{ $is_my_turn ? 'Pilih salah satu matkul di bawah ini.' : 'Lawanmu sedang memilih, tunggu sebentar…' }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-3">
                    @if ($running)
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-navy-500">Sisa waktu</p>
                            <p class="text-4xl font-bold tabular-nums {{ $is_my_turn ? 'text-cyan-600' : 'text-navy-800' }}">
                                <span data-timer-label>{{ $seconds_left }}</span><span class="text-lg text-navy-400"> dtk</span>
                            </p>
                        </div>
                        <div class="h-2.5 w-40 overflow-hidden rounded-full bg-navy-100">
                            <div data-timer-bar class="h-full bg-cyan-500 transition-[width] duration-200 ease-linear" style="width: {{ $limit > 0 ? (int) round($seconds_left / $limit * 100) : 0 }}%"></div>
                        </div>
                    @else
                        <p class="text-sm font-semibold text-navy-500">Draft ronde ini selesai.</p>
                    @endif
                </div>
            </div>

            {{-- Penanda langkah: Ban 1 → Ban 2 → Pick 1 → Pick 2 --}}
            <ol class="mt-5 grid grid-cols-4 gap-2" aria-label="Langkah draft">
                @foreach ($stepLabels as $index => $label)
                    <li @class([
                        'rounded-lg px-3 py-2 text-center text-xs font-bold',
                        'bg-ungu-500 text-white' => $index === $step && $is_my_turn,
                        'bg-navy-900 text-white' => $index === $step && ! $is_my_turn,
                        'bg-ungu-100 text-ungu-700' => $index < $step,
                        'bg-navy-50 text-navy-500' => $index > $step,
                    ])>
                        {{ $label }}
                        @if ($index < $step)
                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide">selesai</span>
                        @elseif ($index === $step)
                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide">sekarang</span>
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
                            <span class="w-12 shrink-0 text-xs font-bold uppercase tracking-wide text-navy-500">R{{ $roundNumber }}</span>
                            @foreach ($roundPicks as $pick)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-navy-900 px-3 py-1 text-xs font-semibold text-white">
                                    {{ $pick['course_name'] }}
                                    <span class="text-navy-300">· {{ (int) $pick['user_id'] === (int) $user->id ? 'kamu' : $pick['user_name'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-navy-500">Matkul yang sudah di-pick hangus untuk ronde berikutnya. Matkul yang di-ban kembali tersedia.</p>
            @endif
        </x-card>

        {{-- Pool matkul --}}
        <section>
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold">Pool matkul ({{ $pool->count() }} terundi)</h2>
                <p class="text-xs text-navy-500">Diundi otomatis dari matkul dengan minimal {{ \App\Services\DraftService::MIN_QUESTIONS }} soal aktif.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pool as $course)
                    @php
                        $pickable = $is_my_turn && $course['available'];

                        if ($course['picked_round']) {
                            $note = 'Dipakai ronde '.$course['picked_round'].' · '.($course['picked_by'] ?? '-');
                            $noteClass = 'bg-red-50 text-red-700 ring-red-200';
                        } elseif (! empty($course['banned_rounds'])) {
                            $note = 'Di-ban ronde '.implode(', ', $course['banned_rounds']).($course['available'] ? ' (boleh dipakai lagi)' : '');
                            $noteClass = 'bg-amber-100 text-amber-800 ring-amber-300';
                        } elseif ($course['available']) {
                            $note = 'Siap dipilih';
                            $noteClass = 'bg-cyan-100 text-cyan-800 ring-cyan-200';
                        } else {
                            $note = 'Tidak tersedia';
                            $noteClass = 'bg-slate-100 text-slate-600 ring-slate-300';
                        }
                    @endphp

                    @if ($pickable)
                        <form method="POST" action="{{ route('duels.draft.store', $duel) }}" class="js-act">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $course['id'] }}">
                            <button type="submit"
                                class="block h-full w-full rounded-xl border-2 border-navy-100 bg-white p-4 text-left transition hover:border-ungu-500 hover:bg-ungu-50 focus:outline-none focus-visible:border-ungu-500 focus-visible:ring-4 focus-visible:ring-ungu-100">
                                @include('duels.partials.course-card', ['course' => $course, 'note' => $note, 'noteClass' => $noteClass])
                                <span class="mt-3 inline-flex rounded-md bg-ungu-500 px-3 py-1 text-xs font-bold text-white">
                                    {{ $action === 'ban' ? 'Ban matkul' : 'Pick matkul' }}
                                </span>
                            </button>
                        </form>
                    @else
                        <div @class([
                            'rounded-xl border-2 border-navy-100 bg-white p-4',
                            'opacity-70' => $course['available'],
                            'opacity-50' => ! $course['available'],
                        ])>
                            @include('duels.partials.course-card', ['course' => $course, 'note' => $note, 'noteClass' => $noteClass])
                            <span class="mt-3 inline-flex rounded-md bg-navy-50 px-3 py-1 text-xs font-bold text-navy-500">
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
                    Belum ada langkah. Mulai dari ban pertama.
                </p>
            @else
                <ol class="divide-y divide-navy-50">
                    @foreach ($history as $row)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                            <span class="flex items-center gap-3 text-sm">
                                <span @class([
                                    'w-16 rounded-md px-2 py-1 text-center text-xs font-bold uppercase',
                                    'bg-red-50 text-red-700' => $row['action'] === 'ban',
                                    'bg-ungu-100 text-ungu-700' => $row['action'] === 'pick',
                                ])>{{ $row['action'] }}</span>
                                <span class="font-semibold">{{ $row['course_name'] }}</span>
                                <span class="text-navy-500">· {{ (int) $row['user_id'] === (int) $user->id ? 'kamu' : $row['user_name'] }}</span>
                            </span>
                            <span class="text-xs text-navy-500">
                                R{{ $row['round'] }} · langkah {{ $row['step'] + 1 }}
                                @if ($row['is_auto'])
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-bold uppercase text-amber-800 ring-1 ring-inset ring-amber-300">auto</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>

    <script>
        (() => {
            const root = document.getElementById('draft');
            if (!root) return;

            const limit = Number(root.dataset.limit);
            const label = root.querySelector('[data-timer-label]');
            const bar = root.querySelector('[data-timer-bar]');
            let end = Date.now() + Number(root.dataset.seconds) * 1000;
            let expired = false;
            let sending = false;

            // Kunci seluruh form setelah satu langkah dikirim (cegah double submit)
            root.querySelectorAll('form.js-act').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (sending) return event.preventDefault();
                    sending = true;
                    root.querySelectorAll('form.js-act button').forEach((button) => button.setAttribute('disabled', 'disabled'));
                });
            });

            const tick = () => {
                const left = Math.max(0, (end - Date.now()) / 1000);

                if (label) label.textContent = Math.ceil(left);
                if (bar) {
                    bar.style.width = `${limit > 0 ? (left / limit) * 100 : 0}%`;
                    bar.classList.toggle('bg-red-500', left <= 5);
                    bar.classList.toggle('bg-cyan-500', left > 5);
                }

                if (left <= 0 && !expired && Number(root.dataset.seconds) > 0) {
                    // Serahkan ke server: endpoint state berikutnya yang akan auto-pick
                    expired = true;
                    setTimeout(() => window.location.reload(), 1000);
                    return;
                }

                setTimeout(tick, 250);
            };
            if (Number(root.dataset.seconds) > 0) tick();

            const poll = async () => {
                try {
                    const res = await fetch(root.dataset.stateUrl, { headers: { Accept: 'application/json' } });
                    const data = await res.json();

                    if (data.done) return window.location.assign(data.redirect);

                    if (Number(data.step) !== Number(root.dataset.step) ||
                        Number(data.turn_user_id) !== Number(root.dataset.turn)) {
                        return window.location.reload();
                    }

                    end = Date.now() + Number(data.seconds_left) * 1000;
                } catch {}
                setTimeout(poll, 2000);
            };
            setTimeout(poll, 2000);
        })();
    </script>
@endsection
