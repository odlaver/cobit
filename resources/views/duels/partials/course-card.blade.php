<span class="flex items-start justify-between gap-2">
    <span>
        <span class="block text-xs font-bold tracking-wide text-cyan-800">{{ $course['code'] }}</span>
        <span class="mt-1 block font-semibold leading-snug text-navy-900">{{ $course['name'] }}</span>
        <span class="mt-1 block text-xs text-navy-500">Semester {{ $course['semester'] }}</span>
    </span>
    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $noteClass }}">
        {{ $note }}
    </span>
</span>
