@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}!</h1>
            <p class="mt-1 text-sm text-navy-600">Kamu di tier {{ auth()->user()->tier }} dengan {{ auth()->user()->points }} poin.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-button variant="secondary" :href="route('challenges.index')">Tantangan masuk</x-button>
            <x-button :href="route('challenges.opponents')">Cari lawan</x-button>
        </div>
    </div>

    <section>
        <div class="mb-3 flex items-baseline justify-between gap-4">
            <h2 class="text-lg font-bold">Statistik duel</h2>
            <a href="{{ route('duels.index') }}" class="text-sm font-semibold text-ungu-600 underline-offset-2 hover:underline">Riwayat duel</a>
        </div>
        <x-duel-stats :user="auth()->user()" />
    </section>

    @include('challenges.partials.watch')
@endsection
