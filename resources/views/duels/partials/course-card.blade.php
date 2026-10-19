<span class="block min-w-0">
    <span class="flex items-baseline justify-between gap-2 text-xs">
        <span class="font-bold tracking-wide text-cyan-800">{{ $course['code'] }}</span>
        <span class="shrink-0 text-navy-600">Semester {{ $course['semester'] }}</span>
    </span>
    <span class="mt-1 block font-semibold leading-snug break-words text-navy-900">{{ $course['name'] }}</span>
    <x-badge :tone="$noteTone" class="mt-2">{{ $note }}</x-badge>
</span>
