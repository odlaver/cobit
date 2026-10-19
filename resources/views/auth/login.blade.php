@extends('layouts.app')

@section('title', 'Masuk')
@section('width', 'max-w-sm')

@section('content')
    <div class="mx-auto w-full max-w-sm pt-6 sm:pt-12">
        <div class="mb-8 text-center">
            <p class="text-3xl font-extrabold tracking-tight text-navy-900">CO<span class="text-ungu-500">BIT</span></p>
            <p class="mt-1 text-sm font-medium text-navy-600">Combat of Brains in Tournament</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke akunmu</h1>
            <p class="mt-1.5 text-sm leading-6 text-slate-600">Lanjutkan duel dan naikkan tier-mu.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf
                <x-input name="email" label="Email" type="email" :value="old('email')" placeholder="nama@email.com" required autofocus autocomplete="username" />
                <x-input name="password" label="Kata sandi" type="password" placeholder="••••••••" required autocomplete="current-password" />

                <label class="flex min-h-11 cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 accent-ungu-500"> Ingat saya
                </label>

                <x-button class="w-full">Masuk</x-button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-ungu-600 hover:text-ungu-700">Daftar sekarang</a>
        </p>
    </div>
@endsection
