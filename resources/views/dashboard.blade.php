@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}!</h1>

    <section class="mt-6">
        <div class="mb-3 flex items-baseline justify-between gap-4">
            <h2 class="text-lg font-bold">Statistik duel</h2>
            <a href="{{ route('duels.index') }}" class="text-sm font-semibold text-ungu-600 underline-offset-2 hover:underline">Riwayat duel</a>
        </div>
        <x-duel-stats :user="auth()->user()" />
    </section>
@endsection
