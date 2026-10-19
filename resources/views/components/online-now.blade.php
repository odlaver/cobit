<div class="flex min-h-11 items-center justify-between gap-4">
    <h2 class="text-lg font-bold">Lagi online</h2>
    @if ($total > $players->count())
        <a href="{{ route('challenges.opponents', ['online' => 1]) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-ungu-600 underline-offset-2 hover:underline">Lihat semua {{ $total }}</a>
    @endif
</div>

@if ($players->isEmpty())
    <p class="mt-3 rounded-xl border border-dashed border-navy-200 bg-white px-5 py-4 text-sm text-navy-600">Belum ada mahasiswa lain yang online.</p>
@else
    <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($players as $player)
            @php $isBusy = in_array($player->id, $busy, true); @endphp
            <li class="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-navy-100 bg-white p-3">
                <span class="flex min-w-0 items-center gap-3">
                    <x-avatar :user="$player" size="sm" :presence="$player->presence($isBusy)" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $player->name }}</span>
                        <x-presence :user="$player" :state="$player->presence($isBusy)" class="text-xs text-navy-600" />
                    </span>
                </span>

                @if (in_array($player->id, $received, true))
                    <a href="{{ route('challenges.index') }}" class="act shrink-0">Jawab</a>
                @elseif (in_array($player->id, $sent, true))
                    <span class="shrink-0 text-xs font-semibold text-navy-600">Terkirim</span>
                @elseif (! $isBusy)
                    <form method="POST" action="{{ route('challenges.store') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="opponent_id" value="{{ $player->id }}">
                        <button type="submit" class="act">Tantang</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endif
