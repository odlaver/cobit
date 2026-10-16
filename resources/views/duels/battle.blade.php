@extends('layouts.app')

@section('title', 'Battle Ronde ' . $round->round_number)

@section('content')
    @php
        $position = $myProgress + 1;
        $cells = collect(range(1, $total))->map(fn ($n) => $n < $position ? 'done' : ($n === $position ? 'current' : 'pending'))->all();
        $question = $item->question;
    @endphp

    <div id="battle" class="mx-auto max-w-3xl"
        data-remaining="{{ $remaining }}" data-limit="{{ $limit }}"
        data-state-url="{{ route('duels.battle.state', ['duel' => $duel, 'round' => $round->round_number]) }}">

        @include('duels.partials.header')

        <div class="mb-3 flex flex-wrap items-end justify-between gap-2 text-sm">
            <p class="font-semibold">Soal {{ $position }} dari {{ $total }}</p>
            <p class="text-navy-500" aria-live="polite">
                {{ $opponent->name }}: <span data-opponent-progress class="font-semibold text-navy-800">{{ $opponentProgress }}</span>/{{ $total }} terjawab
            </p>
        </div>
        <x-bit-strip :cells="$cells" label="Progres jawabanmu" class="mb-6" />

        <x-card class="p-0!">
            <div class="h-2 overflow-hidden rounded-t-xl bg-navy-100">
                <div data-timer-bar class="h-full bg-cyan-500 transition-[width] duration-200 ease-linear" style="width: {{ $remaining / $limit * 100 }}%"></div>
            </div>

            <div class="p-6 sm:p-8">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <p class="text-sm font-medium text-ungu-600">{{ $question->material->course->name }} · {{ $question->material->title }}</p>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-navy-800">
                        <span data-timer-label>{{ (int) ceil($remaining) }}</span> dtk
                    </p>
                </div>

                <h2 class="mb-8 text-lg leading-relaxed font-semibold whitespace-pre-line sm:text-xl">{{ $question->question_text }}</h2>

                <form method="POST" action="{{ route('duels.battle.answer', $duel) }}" data-answer-form>
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

                <p class="mt-6 hidden text-xs text-navy-500 sm:block">Bisa juga tekan A, B, C, atau D di keyboard. Kalau waktu habis, soal dianggap salah.</p>
            </div>
        </x-card>
    </div>

    <script>
        (() => {
            const root = document.getElementById('battle');
            const form = root.querySelector('[data-answer-form]');
            const bar = root.querySelector('[data-timer-bar]');
            const label = root.querySelector('[data-timer-label]');
            const limit = Number(root.dataset.limit);
            const end = Date.now() + Number(root.dataset.remaining) * 1000;
            let sent = false;

            // Kunci form setelah terkirim
            form.addEventListener('submit', (event) => {
                if (sent) return event.preventDefault();
                sent = true;
                form.querySelectorAll('button').forEach((b) => b.setAttribute('aria-disabled', 'true'));
            });

            const tick = () => {
                const left = Math.max(0, (end - Date.now()) / 1000);
                bar.style.width = `${(left / limit) * 100}%`;
                label.textContent = Math.ceil(left);
                bar.classList.toggle('bg-red-500', left <= 5);
                bar.classList.toggle('bg-cyan-500', left > 5);
                if (left <= 0) return form.requestSubmit();
                setTimeout(tick, 200);
            };
            tick();

            document.addEventListener('keydown', (event) => {
                if (event.ctrlKey || event.metaKey || event.altKey) return;
                form.querySelector(`[data-key="${event.key.toLowerCase()}"]`)?.click();
            });

            // Pantau progres lawan
            const poll = async () => {
                try {
                    const res = await fetch(root.dataset.stateUrl, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (data.done) return window.location.assign(data.redirect);
                    root.querySelector('[data-opponent-progress]').textContent = data.opponent;
                } catch {}
                setTimeout(poll, 3000);
            };
            setTimeout(poll, 3000);
        })();
    </script>
@endsection
