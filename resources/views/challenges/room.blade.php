@extends('layouts.app')

@section('title', 'Atur Room')
@section('width', 'max-w-3xl')

@php
    $max = \App\Services\DraftService::POOL_SIZE;
    $blocked = $busy || $pending;
@endphp

@section('content')
    <div class="space-y-6" data-controller="presence">
        <div>
            <a href="{{ route('challenges.opponents') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-navy-600 hover:text-navy-900">
                <x-icon name="back" /> Kembali ke Cari lawan
            </a>
            <h1 class="mt-2 text-2xl font-bold">Atur room duel</h1>
            <div class="mt-3 flex min-w-0 items-center gap-3">
                <x-avatar :user="$opponent" size="sm" :presence="$opponent->presence($busy)" />
                <p class="min-w-0 text-sm text-navy-700">
                    Melawan <span class="font-semibold text-navy-900">{{ $opponent->name }}</span>
                    <span class="text-navy-500">· <x-presence :user="$opponent" :state="$opponent->presence($busy)" class="align-middle" /></span>
                </p>
            </div>
        </div>

        @if ($blocked)
            <p role="status" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $busy ? $opponent->name . ' sedang berduel.' : 'Masih ada tantangan yang menunggu jawaban dengan ' . $opponent->name . '.' }}
            </p>
        @endif

        <form method="POST" action="{{ route('challenges.store') }}" class="group/room space-y-6"
            data-controller="room" data-room-max-value="{{ $max }}" data-action="change->room#update input->room#update">
            @csrf
            <input type="hidden" name="opponent_id" value="{{ $opponent->id }}">
            <input type="hidden" name="from_room" value="1">

            <x-card>
                <fieldset>
                    <legend class="text-lg font-bold text-navy-900">Isi room</legend>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label @class([
                            'flex gap-3 rounded-xl border-2 p-4 transition-colors has-[:checked]:border-ungu-500 has-[:checked]:bg-ungu-50',
                            'cursor-pointer border-navy-100 hover:border-navy-200' => $canRandom,
                            'cursor-not-allowed border-slate-200 bg-slate-50' => ! $canRandom,
                        ])>
                            <input type="radio" id="mode-acak" name="mode" value="acak" class="mt-1 size-4 shrink-0" @checked($mode === 'acak') @disabled(! $canRandom)>
                            <span class="min-w-0">
                                <span class="block font-semibold text-navy-900">Acak</span>
                                <span class="mt-0.5 block text-sm text-navy-600">
                                    {{ $canRandom ? $max . ' matkul acak dengan ban dan pick.' : 'Matkul siap duel kurang dari ' . $max . '.' }}
                                </span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-xl border-2 border-navy-100 p-4 transition-colors hover:border-navy-200 has-[:checked]:border-ungu-500 has-[:checked]:bg-ungu-50">
                            <input type="radio" id="mode-pilih" name="mode" value="pilih" class="mt-1 size-4 shrink-0" @checked($mode === 'pilih')>
                            <span class="min-w-0">
                                <span class="block font-semibold text-navy-900">Pilih matkul</span>
                                <span class="mt-0.5 block text-sm text-navy-600">1 sampai {{ $max }} matkul pilihanmu.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>
            </x-card>

            {{-- Daftar matkul, tersembunyi saat mode acak --}}
            <x-card class="group-has-[#mode-acak:checked]/room:hidden">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 class="text-lg font-bold text-navy-900">Matkul yang siap duel</h2>
                    <p class="text-sm text-navy-600" aria-live="polite"><span data-room-target="count">{{ count($selected) }}</span> dari maks {{ $max }} dipilih</p>
                </div>

                @if ($courses->isEmpty())
                    <p class="mt-4 rounded-xl border border-dashed border-navy-200 px-5 py-6 text-center text-sm text-navy-600">
                        Belum ada matkul dengan minimal {{ \App\Services\DraftService::MIN_QUESTIONS }} soal aktif.
                    </p>
                @else
                    <div class="mt-4">
                        <label for="room-search" class="sr-only">Cari matkul</label>
                        <input id="room-search" type="search" data-room-target="search" data-action="keydown.enter->room#ignore" placeholder="Cari kode atau nama matkul" autocomplete="off" class="field">
                    </div>

                    @error('course_ids')
                        <p id="course_ids-error" class="mt-3 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 space-y-5">
                        @foreach ($courses->groupBy('semester') as $semester => $group)
                            <fieldset data-room-target="group">
                                <legend class="text-xs font-bold uppercase tracking-wide text-navy-600">Semester {{ $semester }}</legend>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($group as $course)
                                        <label data-room-target="option" data-search="{{ mb_strtolower($course->code . ' ' . $course->name) }}"
                                            class="flex min-h-11 cursor-pointer items-start gap-3 rounded-lg border border-navy-100 px-3 py-2.5 transition-colors hover:border-navy-200 has-[:checked]:border-ungu-500 has-[:checked]:bg-ungu-50 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
                                            <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" data-room-target="checkbox" data-name="{{ $course->name }}"
                                                class="mt-0.5 size-4 shrink-0" @checked(in_array($course->id, $selected, true))>
                                            <span class="min-w-0 text-sm">
                                                <span class="font-bold text-cyan-800">{{ $course->code }}</span>
                                                <span class="block break-words text-navy-900">{{ $course->name }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        <p data-room-target="empty" hidden class="rounded-xl border border-dashed border-navy-200 px-5 py-6 text-center text-sm text-navy-600">
                            Tidak ada matkul yang cocok.
                        </p>
                    </div>
                @endif
            </x-card>

            <x-card>
                <fieldset>
                    <legend class="text-lg font-bold text-navy-900">Jumlah ronde</legend>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([1 => ['1 ronde', \App\Services\BattleService::QUESTIONS_PER_ROUND . ' soal.'], 3 => ['Best of 3', 'Menang 2 ronde lebih dulu.']] as $value => [$label, $hint])
                            <label class="flex cursor-pointer gap-3 rounded-xl border-2 border-navy-100 p-4 transition-colors hover:border-navy-200 has-[:checked]:border-ungu-500 has-[:checked]:bg-ungu-50">
                                <input type="radio" name="rounds" value="{{ $value }}" class="mt-1 size-4 shrink-0" @checked($rounds === $value)>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-navy-900">{{ $label }}</span>
                                    <span class="mt-0.5 block text-sm text-navy-600">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </x-card>

            <div class="flex flex-col gap-4 rounded-xl bg-navy-900 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between">
                <p class="min-w-0 text-sm font-semibold break-words" aria-live="polite" data-room-target="summary">Room siap dikirim.</p>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('challenges.opponents') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-navy-100 transition-colors hover:bg-navy-800 hover:text-white">Batal</a>
                    <x-button type="submit" data-room-target="submit" :disabled="$blocked">Kirim tantangan</x-button>
                </div>
            </div>
        </form>
    </div>
@endsection
