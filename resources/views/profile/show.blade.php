@extends('layouts.app')

@section('title', 'Profil ' . $user->name)
@section('width', 'max-w-2xl')

@section('content')
    <x-card>
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
            <x-avatar :user="$user" size="lg" />

            <div class="flex-1 text-center sm:text-left">
                <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                    @if ($user->isAdmin())
                        <span class="rounded-full bg-navy-800 px-2.5 py-0.5 text-xs font-bold uppercase text-white">Admin</span>
                    @else
                        <x-tier-badge :points="$user->points" />
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                    @if ($user->npm)
                        <dt class="text-navy-500">NPM</dt><dd class="font-medium">{{ $user->npm }}</dd>
                    @endif
                    <dt class="text-navy-500">Prodi</dt><dd class="font-medium">{{ $user->prodi }}</dd>
                    @if ($user->angkatan)
                        <dt class="text-navy-500">Angkatan</dt><dd class="font-medium">{{ $user->angkatan }}</dd>
                    @endif
                    @unless ($user->isAdmin())
                        <dt class="text-navy-500">Poin</dt><dd class="font-bold text-ungu-600">{{ $user->points }}</dd>
                        <dt class="text-navy-500">Peringkat</dt><dd class="font-medium">#{{ $rank }}</dd>
                    @endunless
                </dl>

                @if ($user->is(auth()->user()))
                    <div class="mt-6">
                        <x-button variant="secondary" :href="route('profile.edit')">Edit profil</x-button>
                    </div>
                @endif
            </div>
        </div>
    </x-card>
@endsection
