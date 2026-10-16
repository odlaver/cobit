<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-ungu-600">Ronde {{ $round->round_number }}</p>
        <h1 class="text-xl font-bold sm:text-2xl">{{ $courses->join(' × ') }}</h1>
    </div>

    <div class="flex items-center gap-3 rounded-xl bg-navy-900 px-4 py-2 text-white">
        <span class="text-sm text-navy-100">Kamu</span>
        <span class="text-2xl font-bold tabular-nums">{{ $roundWins[0] }}<span class="px-1.5 text-navy-200">:</span>{{ $roundWins[1] }}</span>
        <span class="flex items-center gap-2 text-sm text-navy-100">
            <x-avatar :user="$opponent" size="sm" />
            <span class="max-w-28 truncate">{{ $opponent->name }}</span>
        </span>
    </div>
</div>
