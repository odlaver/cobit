@extends('layouts.app')

@section('title', 'Daftar')
@section('description', 'Daftar akun COBIT untuk ikut duel kuis 1 lawan 1 antar mahasiswa, kumpulkan poin, dan naik tier.')
@section('width', 'max-w-sm')

@section('content')
    <div class="mx-auto w-full max-w-sm pt-6 sm:pt-12">
        <div class="mb-8 text-center">
            <p class="text-3xl font-extrabold tracking-tight text-navy-900">CO<span class="text-ungu-500">BIT</span></p>
            <p class="mt-1 text-sm font-medium text-navy-600">Combat of Brains in Tournament</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buat akun</h1>
            <p class="mt-1.5 text-sm leading-6 text-slate-600">Khusus mahasiswa SI angkatan 24.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
                @csrf
                <x-input name="name" label="Nama lengkap" :value="old('name')" placeholder="Nama sesuai KTM" required autofocus autocomplete="name" />
                <x-input name="npm" label="NPM" :value="old('npm')" placeholder="Nomor Pokok Mahasiswa" required inputmode="numeric" />
                <x-input name="email" label="Email" type="email" :value="old('email')" placeholder="nama@email.com" required autocomplete="username" />
                <x-input name="password" label="Kata sandi" type="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
                <x-input name="password_confirmation" label="Ulangi kata sandi" type="password" placeholder="Ketik ulang kata sandi" required autocomplete="new-password" />

                <x-button class="w-full">Daftar</x-button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-ungu-600 hover:text-ungu-700">Masuk</a>
        </p>
    </div>
@endsection
