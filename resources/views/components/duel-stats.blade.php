<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if ($active)
        @php $rival = $active->opponentOf($user); @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-navy-900 px-5 py-4 text-white">
            <p class="text-sm">
                Duel melawan <strong>{{ $rival->name }}</strong> masih berjalan (ronde {{ $active->current_round }}).
            </p>
            <x-button :href="route('duels.show', $active)">Lanjutkan duel</x-button>
        </div>
    @endif

    @if ($total === 0)
        <p class="rounded-xl border border-dashed border-navy-200 bg-white px-5 py-4 text-sm text-navy-600">
            Belum ada duel yang selesai. Statistik menang-kalah muncul setelah duel pertamamu.
        </p>
    @else
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl bg-ungu-600 px-5 py-4 text-white">
                <dt class="text-sm text-ungu-100">Menang</dt>
                <dd class="text-3xl font-bold tabular-nums">{{ $wins }}</dd>
            </div>
            <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
                <dt class="text-sm text-navy-600">Kalah</dt>
                <dd class="text-3xl font-bold tabular-nums">{{ $losses }}</dd>
            </div>
            <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
                <dt class="text-sm text-navy-600">Seri</dt>
                <dd class="text-3xl font-bold tabular-nums">{{ $draws }}</dd>
            </div>
            <div class="rounded-xl border border-navy-100 bg-white px-5 py-4">
                <dt class="text-sm text-navy-600">Win rate</dt>
                <dd class="text-3xl font-bold tabular-nums">{{ round($wins / $total * 100) }}%</dd>
            </div>
        </dl>
    @endif
</div>
