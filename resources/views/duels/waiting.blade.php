@extends('layouts.app')

@section('title', 'Menunggu Lawan')

@section('content')
    <div id="waiting" class="mx-auto max-w-3xl"
        data-state-url="{{ route('duels.battle.state', ['duel' => $duel, 'round' => $round->round_number]) }}">

        @include('duels.partials.header')

        <x-card class="text-center">
            <p class="text-sm font-semibold text-ungu-600">Semua {{ $total }} soal sudah kamu jawab</p>
            <h2 class="mt-2 text-xl font-bold">Menunggu {{ $opponent->name }} selesai</h2>

            <div class="mx-auto mt-6 max-w-sm">
                <x-bit-strip :cells="collect(range(1, $total))->map(fn ($n) => $n <= $opponentProgress ? 'done' : 'pending')->all()"
                    label="Progres {{ $opponent->name }}" data-opponent-strip />
                <p class="mt-3 text-sm text-navy-500" aria-live="polite" data-status>
                    {{ $opponentProgress }} dari {{ $total }} soal terjawab
                </p>
            </div>

            <p class="mt-6 text-xs text-navy-500">
                Hasil ronde muncul otomatis. Kalau lawan tidak menyelesaikan, ronde ditutup setelah batas waktu.
                <a href="{{ route('duels.battle', $duel) }}" class="font-semibold text-ungu-600 underline-offset-2 hover:underline">Cek sekarang</a>
            </p>
        </x-card>
    </div>

    <script>
        (() => {
            const root = document.getElementById('waiting');
            const status = root.querySelector('[data-status]');
            const cells = root.querySelectorAll('[data-opponent-strip] li');

            // Cek status ronde tiap 2 detik
            const poll = async () => {
                try {
                    const res = await fetch(root.dataset.stateUrl, { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    if (data.done) return window.location.assign(data.redirect);
                    status.textContent = `${data.opponent} dari ${data.total} soal terjawab`;
                    cells.forEach((cell, i) => {
                        cell.classList.toggle('bg-ungu-500', i < data.opponent);
                        cell.classList.toggle('bg-navy-100', i >= data.opponent);
                    });
                } catch {
                    status.textContent = 'Koneksi terputus, mencoba lagi...';
                }
                setTimeout(poll, 2000);
            };
            setTimeout(poll, 2000);
        })();
    </script>
@endsection
